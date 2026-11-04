<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Links;

use function Safe\filesize;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final readonly class MessageLinkChecker
{
    public function __construct(private MessageUrls $urls) {}

    /**
     * @return list<array{check: UrlCheck, kinds: list<string>, occurrences: int, url: string}>
     */
    public function check(string $html, string $host): array
    {
        $urls = $this->urls->collect($html);
        $hosts = [new Uri(config()->string('app.url'))->getHost(), $host];
        $checks = [];
        $pending = [];

        foreach ($urls as $url) {
            $pending[$url['url']] = ['redirects' => 0, 'url' => $url['url']];
        }

        while ($pending !== []) {
            $remote = [];

            foreach ($pending as $original => $target) {
                $uri = new Uri($target['url']);

                if (in_array($uri->getHost(), $hosts, true)) {
                    $checks[$original] = $this->redirected(
                        $this->local($uri),
                        $target['redirects'],
                    );
                } else {
                    $remote[$original] = $target;
                }
            }

            $pending = [];
            $responses = $this->request($remote);

            foreach ($remote as $original => $target) {
                $result = $this->remote($target, $responses[$original]);

                if (is_string($result)) {
                    $pending[$original] = [
                        'redirects' => $target['redirects'] + 1,
                        'url' => $result,
                    ];
                } else {
                    $checks[$original] = $this->redirected($result, $target['redirects']);
                }
            }
        }

        return array_map(
            static fn (array $url): array => [...$url, 'check' => $checks[$url['url']]],
            $urls,
        );
    }

    private function local(Uri $uri): UrlCheck
    {
        $url = (string) $uri;
        $file = public_path(UriResolver::removeDotSegments(rawurldecode($uri->getPath())));

        if (is_file($file)) {
            return new UrlCheck('ok', Response::HTTP_OK, $url, filesize($file));
        }

        try {
            $route = Route::getRoutes()->match(Request::create($url));
        } catch (MethodNotAllowedHttpException|NotFoundHttpException) {
            $route = null;
        }

        if (! $route instanceof RoutingRoute || $route->isFallback) {
            return new UrlCheck(
                'broken',
                Response::HTTP_NOT_FOUND,
                $url,
                reason: 'No application GET route or public file matches this URL.',
            );
        }

        return new UrlCheck('ok', Response::HTTP_OK, $url, routeName: $route->getName());
    }

    private function redirected(UrlCheck $check, int $redirects): UrlCheck
    {
        if ($check->status !== 'ok' || $redirects === 0) {
            return $check;
        }

        return new UrlCheck(
            'redirected',
            $check->httpStatus,
            $check->finalUrl,
            $check->contentLength,
            'The URL redirected '.$redirects.' '.Str::plural('time', $redirects).'.',
            $check->routeName,
        );
    }

    /**
     * @param array{redirects: int, url: string} $target
     */
    private function remote(array $target, ClientResponse|Throwable $response): string|UrlCheck
    {
        if ($response instanceof Throwable) {
            return new UrlCheck(
                'unreachable',
                finalUrl: $target['url'],
                reason: 'The server could not be reached (DNS, timeout, or TLS failure).',
            );
        }

        if ($response->redirect() && $response->header('Location') !== '') {
            try {
                $next = UriResolver::resolve(
                    new Uri($target['url']),
                    new Uri($response->header('Location')),
                );
            } catch (InvalidArgumentException) {
                $next = null;
            }

            if (
                ! $next instanceof UriInterface
                || ! in_array($next->getScheme(), ['http', 'https'], true)
                || $next->getHost() === ''
            ) {
                return new UrlCheck(
                    'broken',
                    $response->status(),
                    $target['url'],
                    reason: 'The URL redirects to an address that is not a web URL.',
                );
            }

            return $target['redirects'] < 5 ? (string) $next : new UrlCheck(
                'unreachable',
                $response->status(),
                $target['url'],
                reason: 'The URL exceeded five redirects.',
            );
        }

        $length = filter_var($response->header('Content-Length'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0],
        ]);

        return new UrlCheck(
            $response->successful() ? 'ok' : 'broken',
            $response->status(),
            $target['url'],
            $length === false ? null : $length,
        );
    }

    /**
     * @param array<string, array{redirects: int, url: string}> $targets
     *
     * @return array<array-key, ClientResponse|Throwable>
     */
    private function request(array $targets): array
    {
        $headers = [];
        $responses = Http::pool(function (Pool $pool) use ($targets, &$headers): void {
            foreach ($targets as $original => $target) {
                $pool->as($original)->timeout(5)->connectTimeout(5)->withOptions([
                    'allow_redirects' => false,
                    'on_headers' => static function (ResponseInterface $response) use (
                        &$headers,
                        $original,
                    ): never {
                        $headers[$original] = new ClientResponse($response);

                        throw new RuntimeException('The response headers have been received.');
                    },
                ])->get($target['url']);
            }
        });

        return [...$responses, ...$headers];
    }
}
