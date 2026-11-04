<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Http\Controllers;

use HosmelQ\MessagePreview\Http\Requests\PreviewRequest;
use HosmelQ\MessagePreview\MessageCatalog;
use HosmelQ\MessagePreview\Rendering\MessageRenderer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class PreviewAttachmentController
{
    public function show(
        PreviewRequest $request,
        MessageCatalog $catalog,
        MessageRenderer $renderer,
        string $message,
        string $variant,
        int $index,
    ): StreamedResponse {
        $configured = $catalog->find($message, $variant) ?? abort(Response::HTTP_NOT_FOUND);
        $email = $renderer->render($configured, $variant, $request->locale())->email;
        $attachment = array_values($email->getAttachments())[$index]
            ?? abort(Response::HTTP_NOT_FOUND);
        $body = $attachment->getBody();

        return response()->streamDownload(
            static function () use ($body): void {
                echo $body;
            },
            $attachment->getFilename() ?? 'attachment-'.$index,
            ['Content-Type' => $attachment->getContentType()],
        );
    }
}
