<?php

declare(strict_types=1);

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\Fluent\AssertableJson;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function (): void {
    config(['message-preview.discovery.paths' => [__DIR__.'/../../Fixtures/Messages']]);
});

it('rejects unknown selections', function (array $parameters, int $status): void {
    $this->getJson(
        route('message-preview.api.previews.show', ['variant' => 'default', ...$parameters]),
    )
        ->assertStatus($status)
        ->assertJsonStructure(['message']);
})->with([
    'array locale' => [
        [
            'locale' => ['en'],
            'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.TextReceipt',
        ],
        Response::HTTP_UNPROCESSABLE_ENTITY,
    ],
    'undiscovered class' => [['message' => 'Illuminate.Mail.Mailable'], Response::HTTP_NOT_FOUND],
    'unknown locale' => [
        [
            'locale' => 'unknown',
            'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.TextReceipt',
        ],
        Response::HTTP_UNPROCESSABLE_ENTITY,
    ],
    'unknown message' => [['message' => 'Unknown.Message'], Response::HTTP_NOT_FOUND],
    'unknown variant' => [
        [
            'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.TextReceipt',
            'variant' => 'missing',
        ],
        Response::HTTP_NOT_FOUND,
    ],
]);

it('returns the exception of a message that fails to render', function (): void {
    $this->getJson(route('message-preview.api.previews.show', [
        'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.BrokenConfiguration',
        'variant' => 'default',
    ]))
        ->assertOk()
        ->assertJsonPath('data.status', 'failed')
        ->assertJsonPath('data.error.exception', RuntimeException::class)
        ->assertJsonPath('data.error.file', fn (string $file): bool => str_ends_with(
            $file,
            'tests/Fixtures/Messages/BrokenConfiguration.php',
        ))
        ->assertJsonPath('data.error.line', fn (mixed $line): bool => is_int($line))
        ->assertJsonPath('data.error.message', 'The preview configuration is unavailable.');
});

it('carries the selected locale in every URL', function (): void {
    $response = $this->getJson(route('message-preview.api.previews.show', [
        'locale' => 'es',
        'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.RichReceipt',
        'variant' => 'default',
    ]))->assertOk();

    expect([
        ...$response->json('data.urls'),
        ...array_column($response->json('data.attachments'), 'url'),
    ])->each->toContain('locale=es');
});

it('keeps the data wrapper when the application disables resource wrapping', function (
    string $message,
    string $status,
): void {
    JsonResource::withoutWrapping();

    $this->getJson(route('message-preview.api.previews.show', [
        'message' => $message,
        'variant' => 'default',
    ]))
        ->assertOk()
        ->assertJsonPath('data.status', $status);
})->with([
    'failed' => ['HosmelQ.MessagePreview.Tests.Fixtures.Messages.BrokenConfiguration', 'failed'],
    'rendered' => ['HosmelQ.MessagePreview.Tests.Fixtures.Messages.TextReceipt', 'rendered'],
]);

it('leaves out the URLs of the parts a message does not have', function (): void {
    $parameters = [
        'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.TextReceipt',
        'variant' => 'default',
    ];

    $this->getJson(route('message-preview.api.previews.show', $parameters))
        ->assertOk()
        ->assertJsonPath('data.html', null)
        ->assertJsonPath('data.html_size', null)
        ->assertJsonPath('data.urls.html', null)
        ->assertJsonPath('data.urls.text', route('message-preview.previews.text', $parameters));
});

it('renders previews when the database is unavailable', function (string $connection): void {
    config([
        'database.connections.unavailable' => [
            'database' => __DIR__.'/missing.sqlite',
            'driver' => 'sqlite',
        ],
        'database.default' => $connection,
    ]);

    $this->getJson(route('message-preview.api.previews.show', [
        'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.TextReceipt',
        'variant' => 'default',
    ]))
        ->assertOk()
        ->assertJsonPath('data.status', 'rendered');
})->with(['unavailable', 'unconfigured']);

it('reports the missing translations and replacements of a message', function (): void {
    $this->getJson(route('message-preview.api.previews.show', [
        'locale' => 'en',
        'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.TranslatedReceipt',
        'variant' => 'default',
    ]))
        ->assertOk()
        ->assertJsonPath('data.missing_replacements', [
            [
                'key' => 'Signed in as :email',
                'lines' => ['Signed in as :email'],
                'placeholder' => ':email',
            ],
        ])
        ->assertJsonPath(
            'data.missing_translations',
            [['key' => 'messages.absent', 'lines' => ['messages.absent']]],
        );
});

it('returns the rendered message with its envelope, bodies, attachments and download URLs', function (): void {
    Event::fake([
        MessageSending::class,
        MessageSent::class,
        NotificationSending::class,
        NotificationSent::class,
    ]);

    config(['mail.to' => ['address' => 'override@example.test']]);

    $parameters = [
        'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.RichReceipt',
        'variant' => 'default',
    ];

    $this->getJson(route('message-preview.api.previews.show', $parameters))
        ->assertOk()
        ->assertJson(fn (AssertableJson $json): AssertableJson => $json->has(
            'data',
            fn (AssertableJson $data): AssertableJson => $data
                ->where('attachments', [
                    [
                        'content_type' => 'application/pdf',
                        'filename' => 'receipt.pdf',
                        'inline' => false,
                        'size' => 17,
                        'url' => route('message-preview.previews.attachment', [
                            ...$parameters,
                            'index' => 0,
                        ]),
                    ],
                    [
                        'content_type' => 'text/plain',
                        'filename' => 'note.txt',
                        'inline' => false,
                        'size' => 8,
                        'url' => route('message-preview.previews.attachment', [
                            ...$parameters,
                            'index' => 1,
                        ]),
                    ],
                ])
                ->where('bcc', [['address' => 'archive@example.test', 'name' => null]])
                ->where('cc', [['address' => 'finance@example.test', 'name' => 'Finance']])
                ->where('from', [['address' => 'orders@example.test', 'name' => 'Orders']])
                ->where('headers', fn (Collection $headers): bool => $headers->contains([
                    'name' => 'Subject',
                    'value' => 'Receipt SG-2048',
                ]))
                ->where(
                    'html',
                    fn (string $html): bool => str_contains($html, 'Thanks for your order'),
                )
                ->where('html_size', fn (int $size): bool => $size > 0)
                ->where('locale', 'en')
                ->where('missing_replacements', [])
                ->where('missing_translations', [])
                ->where('reply_to', [['address' => 'help@example.test', 'name' => 'Support']])
                ->where('status', 'rendered')
                ->where('subject', 'Receipt SG-2048')
                ->where(
                    'text',
                    fn (string $text): bool => str_contains($text, 'Thanks for your order'),
                )
                ->where('to', [
                    ['address' => 'ana@example.test', 'name' => 'Ana'],
                    ['address' => 'mateo@example.test', 'name' => null],
                ])
                ->where('urls', [
                    'eml' => route('message-preview.previews.eml', $parameters),
                    'html' => route('message-preview.previews.html', $parameters),
                    'text' => route('message-preview.previews.text', $parameters),
                ]),
        ));

    Event::assertNotDispatched(MessageSending::class);
    Event::assertNotDispatched(MessageSent::class);
    Event::assertNotDispatched(NotificationSending::class);
    Event::assertNotDispatched(NotificationSent::class);
});
