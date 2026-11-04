<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Rendering;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Override;

final class PreviewMailChannel extends MailChannel
{
    public function __construct(
        PreviewMailer $mailer,
        Markdown $markdown,
        private readonly null|string $locale,
    ) {
        parent::__construct($mailer, $markdown);
    }

    #[Override]
    public function send(mixed $notifiable, Notification $notification): mixed
    {
        $message = method_exists($notification, 'toMail')
            ? $notification->toMail($notifiable)
            : null;

        if ($message instanceof Mailable) {
            if ($this->locale !== null) {
                $message->locale($this->locale);
            }

            return $message->send($this->mailer);
        }

        if (
            ! $message instanceof MailMessage
            || ! is_object($notifiable)
            || ! method_exists($notifiable, 'routeNotificationFor')
            || blank($notifiable->routeNotificationFor('mail', $notification))
        ) {
            return null;
        }

        return $this->mailer->mailer($message->mailer)->send(
            $this->buildView($message),
            array_merge($message->data(), $this->additionalMessageData($notification)),
            $this->messageBuilder($notifiable, $notification, $message),
        );
    }
}
