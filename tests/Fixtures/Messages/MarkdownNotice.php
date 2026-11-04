<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Tests\Fixtures\Messages;

use HosmelQ\MessagePreview\Support\PreviewConfig;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class MarkdownNotice extends Notification
{
    public static function previewConfig(): PreviewConfig
    {
        return PreviewConfig::configure()->withNotifiable(
            fn (): Recipient => new Recipient('Ana', 'es'),
        );
    }

    public function toMail(Recipient $notifiable): MailMessage
    {
        return new MailMessage()->greeting('Welcome '.$notifiable->name)
            ->line('Your account is ready.');
    }
}
