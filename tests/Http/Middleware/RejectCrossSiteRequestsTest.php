<?php

declare(strict_types=1);

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    config([
        'mail.default' => 'array',
        'mail.mailers' => ['array' => ['transport' => 'array']],
        'message-preview.discovery.paths' => [__DIR__.'/../../Fixtures/Messages'],
    ]);

    Http::fake();
});

it('rejects requests from other sites that change something', function (array $headers): void {
    $routes = collect(Route::getRoutes()->getRoutesByName())->filter(
        fn (RoutingRoute $route, string $name): bool => str_starts_with($name, 'message-preview.')
            && ! in_array('GET', $route->methods(), true),
    );

    expect($routes)->not->toBeEmpty();

    foreach ($routes as $name => $route) {
        $this->post(route($name, [
            'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.RichReceipt',
            'variant' => 'default',
        ]), [
            'clients' => [],
            'html' => '<a href="https://remote.example">Remote</a>',
            'to' => 'attacker@example.test',
        ], $headers)->assertForbidden();
    }

    expect(Mail::mailer('array')->getSymfonyTransport()->messages())->toBeEmpty();

    Http::assertNothingSent();
})->with([
    'cross-site' => [['Sec-Fetch-Site' => 'cross-site']],
    'same-site' => [['Sec-Fetch-Site' => 'same-site']],
    'another origin without fetch metadata' => [['Origin' => 'http://attacker.test']],
    'an opaque origin without fetch metadata' => [['Origin' => 'null']],
]);

it('accepts requests from the preview page and clients without fetch metadata', function (
    array $headers,
): void {
    $this->postJson(route('message-preview.api.previews.send', [
        'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.RichReceipt',
        'variant' => 'default',
    ]), ['to' => 'tester@example.test'], $headers)->assertNoContent();

    expect(Mail::mailer('array')->getSymfonyTransport()->messages())->toHaveCount(1);
})->with([
    'same origin' => [['Sec-Fetch-Site' => 'same-origin']],
    'same origin behind a proxy that terminates TLS' => [
        ['Origin' => 'https://localhost', 'Sec-Fetch-Site' => 'same-origin'],
    ],
    'same origin without fetch metadata' => [['Origin' => 'http://localhost']],
    'without fetch metadata' => [[]],
]);

it('keeps opening the preview from other sites', function (): void {
    $this->get(route('message-preview.index'), ['Sec-Fetch-Site' => 'cross-site'])->assertOk();
});
