<?php

declare(strict_types=1);

use HosmelQ\MessagePreview\MessageCatalog;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

it('registers the message catalog as a singleton', function (): void {
    expect(app(MessageCatalog::class))->toBe(app(MessageCatalog::class));
});

it('serves every route from a package controller', function (): void {
    $controllers = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route): bool => str_starts_with(
            (string) $route->getName(),
            'message-preview.',
        ))
        ->map(fn (RoutingRoute $route): string => (string) $route->getControllerClass());

    expect($controllers)->not->toBeEmpty()
        ->each->toStartWith('HosmelQ\MessagePreview\Http\Controllers\\');
});
