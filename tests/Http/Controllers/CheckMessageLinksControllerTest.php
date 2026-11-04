<?php

declare(strict_types=1);

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

it('requires the HTML to check', function (): void {
    Http::fake();

    $this->postJson(route('message-preview.api.links'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('html');

    Http::assertNothingSent();
});

it('checks the links of the posted HTML', function (): void {
    Http::fake(['*' => Http::response()]);

    $this->postJson(route('message-preview.api.links'), [
        'html' => '<a href="https://remote.example/es?expires=1&amp;signature=a">Page</a>',
    ])
        ->assertOk()
        ->assertExactJson([
            'data' => [[
                'content_length' => null,
                'final_url' => 'https://remote.example/es?expires=1&signature=a',
                'http_status' => Response::HTTP_OK,
                'kinds' => ['link'],
                'occurrences' => 1,
                'reason' => null,
                'route_name' => null,
                'status' => 'ok',
                'url' => 'https://remote.example/es?expires=1&signature=a',
            ]],
        ]);
});

it('keeps the data wrapper when the application disables resource wrapping', function (): void {
    Http::fake(['*' => Http::response()]);
    JsonResource::withoutWrapping();

    $this->postJson(route('message-preview.api.links'), [
        'html' => '<a href="https://remote.example/page">Page</a>',
    ])
        ->assertOk()
        ->assertJsonPath('data.0.url', 'https://remote.example/page');
});
