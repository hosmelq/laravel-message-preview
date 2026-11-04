<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Http\Controllers;

use HosmelQ\MessagePreview\Http\Requests\CheckMessageLinksRequest;
use HosmelQ\MessagePreview\Http\Resources\LinkCheckCollection;
use HosmelQ\MessagePreview\Links\MessageLinkChecker;

final readonly class CheckMessageLinksController
{
    public function __invoke(
        CheckMessageLinksRequest $request,
        MessageLinkChecker $checker,
    ): LinkCheckCollection {
        $links = $checker->check($request->string('html')->value(), $request->getHost());

        return new LinkCheckCollection($links);
    }
}
