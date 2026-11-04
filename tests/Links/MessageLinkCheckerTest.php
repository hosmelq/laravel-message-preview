<?php

declare(strict_types=1);

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response as Psr7Response;
use HosmelQ\MessagePreview\Links\MessageLinkChecker;
use HosmelQ\MessagePreview\Links\UrlCheck;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

it('reports application URLs that only the fallback route matches as broken', function (): void {
    Route::fallback(fn (): never => throw new RuntimeException('The route must not run.'));
    Http::fake();

    $checks = app(MessageLinkChecker::class)->check(
        '<a href="http://localhost/missing">Missing</a>',
        'localhost',
    );

    expect($checks[0]['check'])->toEqual(new UrlCheck(
        'broken',
        Response::HTTP_NOT_FOUND,
        'http://localhost/missing',
        reason: 'No application GET route or public file matches this URL.',
    ));

    Http::assertNothingSent();
});

it('reports broken responses', function (int $status): void {
    Http::fake(['*' => Http::response('', $status)]);

    $checks = app(MessageLinkChecker::class)->check(
        '<a href="https://remote.example/page">Page</a>',
        'localhost',
    );

    expect($checks[0]['check'])->toEqual(
        new UrlCheck('broken', $status, 'https://remote.example/page'),
    );
})->with([Response::HTTP_NOT_FOUND, Response::HTTP_INTERNAL_SERVER_ERROR]);

it('reports redirects to addresses that are not web URLs', function (string $location): void {
    Http::fake(['*' => Http::response('', Response::HTTP_FOUND, ['Location' => $location])]);

    $checks = app(MessageLinkChecker::class)->check(
        '<a href="https://remote.example/page">Page</a>',
        'localhost',
    );

    expect($checks[0]['check'])->toEqual(new UrlCheck(
        'broken',
        Response::HTTP_FOUND,
        'https://remote.example/page',
        reason: 'The URL redirects to an address that is not a web URL.',
    ));

    Http::assertSentCount(1);
})->with(['ftp://remote.example/file', 'http://:80']);

it('reports servers that cannot be reached without losing the other results', function (): void {
    Http::fake([
        'cdn.example/*' => Http::response(),
        'remote.example/*' => Http::failedConnection('cURL error 28: Operation timed out'),
    ]);

    $checks = app(MessageLinkChecker::class)->check(
        '<a href="https://remote.example/page">Page</a><img src="https://cdn.example/image.png">',
        'localhost',
    );

    expect($checks[0]['check'])->toEqual(new UrlCheck(
        'unreachable',
        finalUrl: 'https://remote.example/page',
        reason: 'The server could not be reached (DNS, timeout, or TLS failure).',
    ))
        ->and($checks[1]['check']->status)->toBe('ok');
});

it('stops after five redirects', function (): void {
    Http::fake(['*' => Http::response('', Response::HTTP_FOUND, ['Location' => '/page'])]);

    $checks = app(MessageLinkChecker::class)->check(
        '<a href="https://remote.example/page">Page</a>',
        'localhost',
    );

    expect($checks[0]['check'])->toEqual(new UrlCheck(
        'unreachable',
        Response::HTTP_FOUND,
        'https://remote.example/page',
        reason: 'The URL exceeded five redirects.',
    ));

    Http::assertSentCount(6);
});

it('checks application URLs against its GET routes and public files without requesting them', function (): void {
    config(['app.url' => 'https://app.example']);

    app()->usePublicPath(__DIR__.'/../Fixtures/public');

    $unexpected = fn (): never => throw new RuntimeException('The route must not run.');

    Route::get('/verify/{id}', $unexpected)->middleware('signed')->name('verification.verify');
    Route::post('/post-only', $unexpected);
    Http::fake();

    $checks = array_column(app(MessageLinkChecker::class)->check(
        '<a href="http://localhost/verify/42?signature=invalid">Verify</a>'
            .'<img src="https://app.example/image.svg?version=1">'
            .'<a href="https://app.example/missing">Missing</a>'
            .'<a href="http://localhost/post-only">Post only</a>'
            .'<a href="http://localhost/%2e%2e/lang/de.json">Outside public</a>',
        'localhost',
    ), 'check');

    expect(array_map(
        fn (UrlCheck $check): array => [$check->status, $check->contentLength, $check->routeName],
        $checks,
    ))->toBe([
        ['ok', null, 'verification.verify'],
        ['ok', 46, null],
        ['broken', null, null],
        ['broken', null, null],
        ['broken', null, null],
    ])
        ->and($checks[2])->toEqual(new UrlCheck(
            'broken',
            Response::HTTP_NOT_FOUND,
            'https://app.example/missing',
            reason: 'No application GET route or public file matches this URL.',
        ));

    Http::assertNothingSent();
});

it('follows redirects and reports the final response', function (): void {
    Http::fake([
        'https://cdn.example/final' => Http::response('', Response::HTTP_OK, [
            'Content-Length' => '240',
        ]),
        'https://remote.example/moved' => Http::response('', Response::HTTP_FOUND, [
            'Location' => 'https://cdn.example/final',
        ]),
        'https://remote.example/page' => Http::response('', Response::HTTP_MOVED_PERMANENTLY, [
            'Location' => '/moved',
        ]),
    ]);

    $checks = app(MessageLinkChecker::class)->check(
        '<img src="https://remote.example/page">',
        'localhost',
    );

    expect($checks)->toEqual([[
        'check' => new UrlCheck(
            'redirected',
            Response::HTTP_OK,
            'https://cdn.example/final',
            240,
            'The URL redirected 2 times.',
        ),
        'kinds' => ['image'],
        'occurrences' => 1,
        'url' => 'https://remote.example/page',
    ]]);

    Http::assertSentCount(3);
});

it('matches redirects to the application against its routes', function (): void {
    Route::get('/verified', fn (): never => throw new RuntimeException('The route must not run.'))
        ->name('verified');
    Http::fake([
        '*' => Http::response('', Response::HTTP_FOUND, [
            'Location' => 'http://localhost/verified',
        ]),
    ]);

    $checks = app(MessageLinkChecker::class)->check(
        '<a href="https://remote.example/page">Page</a>',
        'localhost',
    );

    expect($checks[0]['check'])->toEqual(new UrlCheck(
        'redirected',
        Response::HTTP_OK,
        'http://localhost/verified',
        reason: 'The URL redirected 1 time.',
        routeName: 'verified',
    ));

    Http::assertSentCount(1);
});

it('requests each URL with GET and stops reading once the headers arrive', function (): void {
    Http::fake(function (Request $request, array $options): PromiseInterface {
        expect($request->method())->toBe('GET')
            ->and($options)->toMatchArray([
                'allow_redirects' => false,
                'connect_timeout' => 5,
                'timeout' => 5,
            ]);

        try {
            $options['on_headers'](
                new Psr7Response(Response::HTTP_OK, ['Content-Length' => '10000000']),
            );
        } catch (RuntimeException $runtimeException) {
            return Create::rejectionFor(new ConnectionException($runtimeException->getMessage()));
        }

        throw new RuntimeException('The request must stop once the headers arrive.');
    });

    $checks = app(MessageLinkChecker::class)->check(
        '<a href="https://remote.example/page">Page</a>',
        'localhost',
    );

    expect($checks[0]['check'])->toEqual(
        new UrlCheck('ok', Response::HTTP_OK, 'https://remote.example/page', 10_000_000),
    );
});
