<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Http\Controllers;

use HosmelQ\MessagePreview\Http\Requests\PreviewRequest;
use HosmelQ\MessagePreview\MessageCatalog;
use HosmelQ\MessagePreview\Rendering\MessageRenderer;
use Illuminate\Http\Response;

final readonly class PreviewHtmlController
{
    public function show(
        PreviewRequest $request,
        MessageCatalog $catalog,
        MessageRenderer $renderer,
        string $message,
        string $variant,
    ): Response {
        $configured = $catalog->find($message, $variant) ?? abort(Response::HTTP_NOT_FOUND);
        $html = $renderer->render($configured, $variant, $request->locale())->html
            ?? abort(Response::HTTP_NOT_FOUND);

        return response($html);
    }
}
