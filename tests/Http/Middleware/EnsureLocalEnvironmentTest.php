<?php

declare(strict_types=1);

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

it('hides every preview route outside the local environment', function (string $environment): void {
    config(['message-preview.discovery.paths' => [__DIR__.'/../../Fixtures/Messages']]);

    $this->app['env'] = $environment;
    $routes = collect(Route::getRoutes()->getRoutesByName())->filter(
        fn (RoutingRoute $route, string $name): bool => str_starts_with($name, 'message-preview.'),
    );

    expect($routes)->not->toBeEmpty();

    foreach ($routes as $name => $route) {
        $this->json($route->methods()[0], route($name, [
            'index' => 0,
            'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.RichReceipt',
            'variant' => 'default',
        ]), ['to' => 'tester@example.test'])->assertNotFound();
    }
})->with(['production', 'staging', 'testing']);
