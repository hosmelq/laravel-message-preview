<?php

declare(strict_types=1);

use HosmelQ\CanIEmail\CanIEmail;
use HosmelQ\MessagePreview\Compatibility\ClientFamilies;

it('lists the tested client families in the order of the data', function (): void {
    $this->app->instance(
        CanIEmail::class,
        CanIEmail::fromJson(file_get_contents(__DIR__.'/../Fixtures/caniemail.json')),
    );

    expect(app(ClientFamilies::class)->all())->toBe([
        'gmail' => 'Gmail',
        'outlook' => 'Outlook',
        'yahoo' => 'Yahoo! Mail',
    ]);
});
