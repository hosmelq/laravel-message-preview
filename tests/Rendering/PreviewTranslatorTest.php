<?php

declare(strict_types=1);

use HosmelQ\MessagePreview\Rendering\PreviewTranslator;
use Illuminate\Support\Facades\Lang;
use Illuminate\Translation\Translator;

beforeEach(function (): void {
    app()->useLangPath(__DIR__.'/../Fixtures/translations');
    app('translation.loader')->addPath(lang_path());

    Lang::addNamespace('fixture', lang_path());
});

it('calls the missing key handler once for each lookup', function (): void {
    $handled = [];

    app(Translator::class)->handleMissingKeysUsing(function (string $key) use (&$handled): string {
        $handled[] = $key;

        return 'Handled: '.$key;
    });

    $translator = PreviewTranslator::copy(app(Translator::class));

    expect($translator->get('Missing :thing', [], 'es'))->toBe('Handled: Missing :thing')
        ->and($handled)->toBe(['Missing :thing'])
        ->and($translator->missingReplacements('es'))->toBe([
            [
                'key' => 'Missing :thing',
                'lines' => ['Handled: Missing :thing'],
                'placeholder' => ':thing',
            ],
        ]);
});

it('does not report existence checks', function (): void {
    $translator = PreviewTranslator::copy(app(Translator::class));

    expect($translator->has('messages.checked', 'es'))->toBeFalse()
        ->and($translator->hasForLocale('messages.fallback', 'es'))->toBeFalse()
        ->and($translator->missingTranslations('es'))->toBe([]);
});

it('records each text a reported key rendered as', function (): void {
    $translator = PreviewTranslator::copy(app(Translator::class));

    $translator->get('Missing :thing', ['thing' => 'one'], 'es');
    $translator->get('Missing :thing', ['thing' => 'two'], 'es');
    $translator->choice('messages.items', 2, [], 'es');

    expect($translator->missingTranslations('es'))->toBe([
        ['key' => 'Missing :thing', 'lines' => ['Missing one', 'Missing two']],
        ['key' => 'messages.items', 'lines' => ['2 items']],
    ]);
});

it('reports keys the locale does not define even when the fallback does', function (): void {
    $translator = PreviewTranslator::copy(app(Translator::class));

    $translator->get('messages.present', [], 'es');
    $translator->get('messages.fallback', [], 'es');
    $translator->get('fixture::messages.absent', [], 'es');
    $translator->get('Regards,', [], 'es');
    $translator->get('JSON present', [], 'es');

    expect($translator->missingTranslations('es'))->toBe([
        ['key' => 'messages.fallback', 'lines' => ['English fallback']],
        ['key' => 'fixture::messages.absent', 'lines' => ['fixture::messages.absent']],
        ['key' => 'Regards,', 'lines' => ['Regards,']],
    ]);
});

it('reports only unresolved file keys in the fallback locale', function (): void {
    $translator = PreviewTranslator::copy(app(Translator::class));

    $translator->get('messages.absent', [], 'en');
    $translator->get('fixture::messages.absent', [], 'en');
    $translator->get('A source sentence. More source text.', [], 'en');
    $translator->get('messages.present', [], 'en');

    expect(array_column($translator->missingTranslations('en'), 'key'))
        ->toBe(['messages.absent', 'fixture::messages.absent']);
});

it('reports placeholders the translation kept without a replacement', function (
    string $locale,
): void {
    $translator = PreviewTranslator::copy(app(Translator::class));

    $translator->get('messages.greeting', ['name' => 'Ana'], $locale);
    $translator->get('Signed in as :email', [], $locale);
    $translator->get('Signed in as :email', ['email' => 'ana@example.test'], $locale);
    $translator->get('Hello :name', ['name' => ':admin'], $locale);

    expect(array_map(
        fn (array $replacement): array => [$replacement['key'], $replacement['placeholder']],
        $translator->missingReplacements($locale),
    ))->toBe([
        ['messages.greeting', ':order'],
        ['Signed in as :email', ':email'],
    ]);
})->with(['en', 'es']);
