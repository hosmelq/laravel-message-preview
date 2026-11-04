<?php

declare(strict_types=1);

use HosmelQ\MessagePreview\MessageCatalog;
use Illuminate\Support\Str;

it('registers the message catalog as a singleton', function (): void {
    expect(app(MessageCatalog::class))->toBe(app(MessageCatalog::class));
});

it('serves the preview page with its settings without discovering messages', function (): void {
    $this->artisan('vendor:publish', ['--force' => true, '--tag' => 'message-preview-assets']);

    config(['message-preview.discovery.paths' => 'invalid']);

    $page = $this->get(
        route('message-preview.index', ['locale' => 'unknown', 'message' => 'Unknown.Message']),
    )
        ->assertOk()
        ->assertViewIs('message-preview::app')
        ->assertSee('<div id="app"></div>', false)
        ->getContent();

    expect(json_decode(Str::between($page, 'type="application/json">', '</script>'), true))->toBe([
        'app_name' => 'Studio Goods',
        'default_mailer' => config('mail.default'),
        'mailers' => array_keys(config('mail.mailers')),
        'poll_interval' => 5,
    ]);
});
