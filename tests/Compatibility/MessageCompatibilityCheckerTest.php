<?php

declare(strict_types=1);

use HosmelQ\CanIEmail\CanIEmail;
use HosmelQ\CanIEmail\Enums\ClientFamily;
use HosmelQ\CanIEmail\Enums\Support;
use HosmelQ\MessagePreview\Compatibility\ClientSupport;
use HosmelQ\MessagePreview\Compatibility\MessageCompatibilityChecker;

beforeEach(function (): void {
    $this->app->instance(
        CanIEmail::class,
        CanIEmail::fromJson(file_get_contents(__DIR__.'/../Fixtures/caniemail.json')),
    );

    $this->families = [ClientFamily::Gmail, ClientFamily::Outlook, ClientFamily::Yahoo];
});

it('drops the features no chosen client lacks', function (): void {
    $results = app(MessageCompatibilityChecker::class)->check(
        '<p style="color: rgb(0 0 0)"></p>',
        $this->families,
    );

    expect($results)->toBe([]);
});

it('groups the platforms of each client by support', function (): void {
    $results = app(MessageCompatibilityChecker::class)->check(
        '<p style="display: flex"></p>',
        $this->families,
    );

    expect($results)->toHaveCount(1)
        ->and($results[0]['feature']->title)->toBe('display:flex')
        ->and($results[0]['clients'])->toEqual([
            new ClientSupport(
                'gmail',
                Support::None,
                ['Desktop Webmail'],
                ['Android'],
                'Gmail',
                ['Partial support.', 'Not supported.'],
                ['iOS'],
            ),
            new ClientSupport(
                'outlook',
                Support::None,
                [],
                ['Windows'],
                'Outlook',
                ['Not supported.'],
                [],
            ),
            new ClientSupport('yahoo', Support::Full, [], [], 'Yahoo! Mail', [], []),
        ]);
});

it('ignores the families the data does not have', function (): void {
    $results = app(MessageCompatibilityChecker::class)->check(
        '<p style="display: flex"></p>',
        [ClientFamily::Aol, ClientFamily::Outlook],
    );

    expect(array_map(fn (ClientSupport $client): string => $client->id, $results[0]['clients']))
        ->toBe(['outlook']);
});

it('sorts the features by how many clients lack them', function (): void {
    $results = app(MessageCompatibilityChecker::class)->check(
        '<div style="border-radius: 4px"><p style="display: flex; margin: 0"></p></div>',
        $this->families,
    );

    expect(array_map(fn (array $result): string => $result['feature']->title, $results))
        ->toBe(['display:flex', 'margin', 'border-radius']);
});
