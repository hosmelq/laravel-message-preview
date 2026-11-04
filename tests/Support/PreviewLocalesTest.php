<?php

declare(strict_types=1);

use HosmelQ\MessagePreview\Support\PreviewLocales;

it('discovers translation directories and JSON locales with sorted unique configured defaults', function (): void {
    app()->useLangPath(__DIR__.'/../Fixtures/lang');
    config([
        'app.fallback_locale' => 'pt',
        'app.locale' => 'en',
    ]);

    expect(new PreviewLocales()->all())->toBe(['de', 'en', 'fr', 'pt']);
});

it('includes configured locales when the translation directory does not exist', function (): void {
    app()->useLangPath(__DIR__.'/missing');
    config([
        'app.fallback_locale' => 'en',
        'app.locale' => 'en',
    ]);

    expect(new PreviewLocales()->all())->toBe(['en']);
});
