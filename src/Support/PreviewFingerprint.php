<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Support;

use function Safe\hash_update_file;

use Composer\Autoload\ClassLoader;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\View\FileViewFinder;
use Symfony\Component\Finder\Finder;

final class PreviewFingerprint
{
    public function generate(): string
    {
        $paths = [
            ...config('message-preview.discovery.paths'),
            lang_path(),
            database_path('factories'),
        ];
        $views = View::getFinder();

        if ($views instanceof FileViewFinder) {
            $vendors = array_map(
                static fn (string $vendor): string => $vendor.DIRECTORY_SEPARATOR,
                array_keys(ClassLoader::getRegisteredLoaders()),
            );
            $hints = array_filter(
                Arr::flatten($views->getHints()),
                static fn (mixed $path): bool
                    => is_string($path) && ! Str::startsWith($path, $vendors),
            );
            $paths = [...$paths, ...$views->getPaths(), ...$hints];
        }

        $hash = hash_init('xxh128');

        foreach (Finder::create()
            ->files()
            ->in(array_filter($paths, is_dir(...)))
            ->sortByName() as $file) {
            hash_update($hash, $file->getPathname()."\0");
            hash_update_file($hash, $file->getPathname());
        }

        return hash_final($hash);
    }
}
