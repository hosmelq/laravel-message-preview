<?php

declare(strict_types=1);

use function Safe\file_put_contents;
use function Safe\touch;

use Composer\Autoload\ClassLoader;
use HosmelQ\MessagePreview\Support\PreviewFingerprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

beforeEach(function (): void {
    $this->path = sys_get_temp_dir().'/message-preview-'.bin2hex(random_bytes(8));
    $this->vendor = array_key_first(ClassLoader::getRegisteredLoaders())
        .'/message-preview-'
        .bin2hex(random_bytes(8));

    foreach (['database/factories', 'lang', 'messages', 'namespaced', 'views'] as $directory) {
        File::ensureDirectoryExists($this->path.'/'.$directory);
    }

    File::ensureDirectoryExists($this->vendor);

    config([
        'message-preview.discovery.paths' => [$this->path.'/messages', $this->path.'/missing'],
    ]);

    View::addLocation($this->path.'/views');
    View::addNamespace('fingerprint', $this->path.'/namespaced');
    View::addNamespace('vendored', $this->vendor);

    app()->useDatabasePath($this->path.'/database');
    app()->useLangPath($this->path.'/lang');
});

afterEach(function (): void {
    File::deleteDirectory($this->path);
    File::deleteDirectory($this->vendor);
});

it('detects added, changed and removed files in every watched directory', function (
    string $directory,
): void {
    $empty = new PreviewFingerprint()->generate();

    File::ensureDirectoryExists($this->path.'/'.$directory.'/nested');

    file_put_contents($this->path.'/'.$directory.'/nested/watched.php', 'first');

    $added = new PreviewFingerprint()->generate();

    file_put_contents($this->path.'/'.$directory.'/nested/watched.php', 'second');

    $changed = new PreviewFingerprint()->generate();

    File::deleteDirectory($this->path.'/'.$directory.'/nested');

    expect($added)->not->toBe($empty)
        ->and($changed)->not->toBe($added)
        ->and(new PreviewFingerprint()->generate())->toBe($empty);
})->with(['database/factories', 'lang', 'messages', 'namespaced', 'views']);

it('detects an edit that keeps the size and the modification time', function (): void {
    file_put_contents($this->path.'/messages/message.php', 'first');
    touch($this->path.'/messages/message.php', 1_700_000_000);

    $fingerprint = new PreviewFingerprint()->generate();

    file_put_contents($this->path.'/messages/message.php', 'other');
    touch($this->path.'/messages/message.php', 1_700_000_000);

    expect(new PreviewFingerprint()->generate())->not->toBe($fingerprint);
});

it('ignores the order of the configured paths', function (): void {
    file_put_contents($this->path.'/messages/message.php', 'message');
    file_put_contents($this->path.'/views/view.blade.php', 'view');

    config(['message-preview.discovery.paths' => [$this->path.'/messages', $this->path.'/views']]);

    $fingerprint = new PreviewFingerprint()->generate();

    config(['message-preview.discovery.paths' => [$this->path.'/views', $this->path.'/messages']]);

    expect(new PreviewFingerprint()->generate())->toBe($fingerprint);
});

it('ignores views that packages register from the vendor directory', function (): void {
    $fingerprint = new PreviewFingerprint()->generate();

    file_put_contents($this->vendor.'/package.blade.php', 'Package view');

    expect(new PreviewFingerprint()->generate())->toBe($fingerprint);
});
