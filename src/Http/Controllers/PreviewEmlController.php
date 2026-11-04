<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Http\Controllers;

use HosmelQ\MessagePreview\Http\Requests\PreviewRequest;
use HosmelQ\MessagePreview\MessageCatalog;
use HosmelQ\MessagePreview\Rendering\MessageRenderer;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class PreviewEmlController
{
    public function show(
        PreviewRequest $request,
        MessageCatalog $catalog,
        MessageRenderer $renderer,
        string $message,
        string $variant,
    ): StreamedResponse {
        $configured = $catalog->find($message, $variant) ?? abort(Response::HTTP_NOT_FOUND);
        $eml = $renderer->render($configured, $variant, $request->locale())->email->toString();
        $name = Str::afterLast($message, '.')
            .($variant === 'default' ? '' : '-'.$variant)
            .($request->locale() === null ? '' : '-'.$request->locale());

        return response()->streamDownload(
            static function () use ($eml): void {
                echo $eml;
            },
            $name.'.eml',
            ['Content-Type' => 'message/rfc822'],
        );
    }
}
