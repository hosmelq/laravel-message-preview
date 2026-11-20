<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RejectCrossSiteRequests
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $site = $request->headers->get('Sec-Fetch-Site');

        // Browsers send Sec-Fetch-Site only to secure origins, so plain HTTP sites such as
        // http://my-app.test fall back to the Origin header that every browser POST carries.
        abort_unless(
            $request->isMethodSafe()
                || $site === 'same-origin'
                || (
                    $site === null
                    && in_array(
                        $request->headers->get('Origin'),
                        [null, $request->getSchemeAndHttpHost()],
                        true,
                    )
                ),
            Response::HTTP_FORBIDDEN,
        );

        return $next($request);
    }
}
