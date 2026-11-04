<?php

declare(strict_types=1);

use HosmelQ\MessagePreview\Support\PreviewConfig;

it('gives variants the shared notifiable unless they have their own', function (): void {
    $shared = fn (stdClass $message): stdClass => $message;
    $own = fn (stdClass $message): stdClass => $message;
    $config = PreviewConfig::configure()
        ->withVariant('inherited', fn (): stdClass => new stdClass())
        ->withVariant('own', fn (): stdClass => new stdClass(), $own)
        ->withNotifiable($shared);

    expect($config->getNotifiable('default'))->toBe($shared)
        ->and($config->getNotifiable('inherited'))->toBe($shared)
        ->and($config->getNotifiable('own'))->toBe($own);
});

it('lists numeric variant names as strings', function (): void {
    $config = PreviewConfig::configure()->withVariant('2', fn (): stdClass => new stdClass());

    expect($config->getVariantNames())->toBe(['default', '2']);
});

it('lists the default variant first without running any factory', function (): void {
    $unexpected = fn (): never => throw new RuntimeException('The factory must not run.');
    $config = PreviewConfig::configure()
        ->withVariant('empty-state', $unexpected)
        ->withMessage($unexpected);

    expect($config->getVariantNames())->toBe(['default', 'empty-state'])
        ->and($config->getMessage('empty-state'))->toBeInstanceOf(Closure::class);
});

it('replaces a variant added again under the same name', function (): void {
    $message = new stdClass();
    $config = PreviewConfig::configure()
        ->withVariant('default', fn (): stdClass => $message)
        ->withVariant('other', fn (): stdClass => new stdClass())
        ->withVariant('other', fn (): stdClass => $message);

    expect($config->getVariantNames())->toBe(['default', 'other'])
        ->and($config->getMessage('default')())->toBe($message)
        ->and($config->getMessage('other')())->toBe($message);
});
