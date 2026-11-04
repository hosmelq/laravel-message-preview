<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureLocalEnvironment
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(app()->isLocal(), Response::HTTP_NOT_FOUND);

        return $next($request);
    }
}
