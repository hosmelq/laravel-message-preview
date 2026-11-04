<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Http\Controllers;

use HosmelQ\MessagePreview\Http\Requests\SendMessageRequest;
use HosmelQ\MessagePreview\MessageCatalog;
use HosmelQ\MessagePreview\Rendering\MessageRenderer;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

final readonly class SendMessageController
{
    public function __invoke(
        SendMessageRequest $request,
        MessageCatalog $catalog,
        MessageRenderer $renderer,
        string $message,
        string $variant,
    ): Response {
        $configured = $catalog->find($message, $variant) ?? abort(Response::HTTP_NOT_FOUND);
        $email = $renderer->render($configured, $variant, $request->locale())->email;

        $email->to($request->string('to')->value());
        $email->getHeaders()->remove('Cc');
        $email->getHeaders()->remove('Bcc');

        try {
            Mail::mailer($request->string('mailer')->value())->getSymfonyTransport()->send($email);
        } catch (TransportExceptionInterface $transportException) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, $transportException->getMessage());
        }

        return response()->noContent();
    }
}
