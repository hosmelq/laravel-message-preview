<?php

declare(strict_types=1);

use HosmelQ\CanIEmail\CanIEmail;
use Illuminate\Http\Resources\Json\JsonResource;

beforeEach(function (): void {
    $this->app->instance(
        CanIEmail::class,
        CanIEmail::fromJson(file_get_contents(__DIR__.'/../../Fixtures/caniemail.json')),
    );
});

it('requires the clients as a list of families', function (mixed $clients, string $error): void {
    $this->postJson(route('message-preview.api.compatibility'), [
        'clients' => $clients,
        'html' => '<p></p>',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors($error);
})->with([
    'clients that are not families' => [['hotmail'], 'clients.0'],
    'clients with keys' => [['gmail' => 'gmail'], 'clients'],
]);

it('requires the clients to check', function (): void {
    $this->postJson(route('message-preview.api.compatibility'), ['html' => '<p></p>'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('clients');
});

it('requires the HTML to check', function (): void {
    $this->postJson(route('message-preview.api.compatibility'), ['clients' => []])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('html');
});

it('checks the posted HTML against the chosen clients', function (): void {
    $this->postJson(route('message-preview.api.compatibility'), [
        'clients' => ['gmail', 'outlook', 'yahoo'],
        'html' => '<div style="border-radius: 4px"><p style="display: flex"></p></div>',
    ])
        ->assertOk()
        ->assertExactJson([
            'data' => [
                [
                    'clients' => [
                        [
                            'id' => 'gmail',
                            'level' => 'none',
                            'limited' => ['Desktop Webmail'],
                            'missing' => ['Android'],
                            'name' => 'Gmail',
                            'notes' => ['Partial support.', 'Not supported.'],
                            'unknown' => ['iOS'],
                        ],
                        [
                            'id' => 'outlook',
                            'level' => 'none',
                            'limited' => [],
                            'missing' => ['Windows'],
                            'name' => 'Outlook',
                            'notes' => ['Not supported.'],
                            'unknown' => [],
                        ],
                        [
                            'id' => 'yahoo',
                            'level' => 'full',
                            'limited' => [],
                            'missing' => [],
                            'name' => 'Yahoo! Mail',
                            'notes' => [],
                            'unknown' => [],
                        ],
                    ],
                    'title' => 'display:flex',
                    'url' => 'https://www.caniemail.com/features/css-display-flex/',
                ],
                [
                    'clients' => [
                        [
                            'id' => 'gmail',
                            'level' => 'unknown',
                            'limited' => [],
                            'missing' => [],
                            'name' => 'Gmail',
                            'notes' => [],
                            'unknown' => ['iOS'],
                        ],
                        [
                            'id' => 'outlook',
                            'level' => 'partial',
                            'limited' => ['Windows'],
                            'missing' => [],
                            'name' => 'Outlook',
                            'notes' => ['Only on rectangles.'],
                            'unknown' => [],
                        ],
                        [
                            'id' => 'yahoo',
                            'level' => 'full',
                            'limited' => [],
                            'missing' => [],
                            'name' => 'Yahoo! Mail',
                            'notes' => [],
                            'unknown' => [],
                        ],
                    ],
                    'title' => 'border-radius',
                    'url' => 'https://www.caniemail.com/features/css-border-radius/',
                ],
            ],
        ]);
});

it('keeps the data wrapper when the application disables resource wrapping', function (): void {
    JsonResource::withoutWrapping();

    $this->postJson(route('message-preview.api.compatibility'), [
        'clients' => ['outlook'],
        'html' => '<p style="display: flex"></p>',
    ])
        ->assertOk()
        ->assertJsonPath('data.0.title', 'display:flex');
});
