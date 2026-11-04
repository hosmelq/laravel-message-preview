<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Http\Controllers;

use HosmelQ\MessagePreview\Http\Requests\PreviewRequest;
use HosmelQ\MessagePreview\Http\Resources\FailedPreviewResource;
use HosmelQ\MessagePreview\Http\Resources\RenderedPreviewResource;
use HosmelQ\MessagePreview\MessageCatalog;
use HosmelQ\MessagePreview\Rendering\MessageRenderer;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final readonly class PreviewController
{
    public function show(
        PreviewRequest $request,
        MessageCatalog $catalog,
        MessageRenderer $renderer,
        string $message,
        string $variant,
    ): FailedPreviewResource|RenderedPreviewResource {
        $configured = $catalog->find($message, $variant) ?? abort(Response::HTTP_NOT_FOUND);

        try {
            $rendered = $renderer->render($configured, $variant, $request->locale());
        } catch (Throwable $throwable) {
            return new FailedPreviewResource($throwable);
        }

        return new RenderedPreviewResource($rendered);
    }
}
