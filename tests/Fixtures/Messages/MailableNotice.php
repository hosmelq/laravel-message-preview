<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Tests\Fixtures\Messages;

use HosmelQ\MessagePreview\Support\PreviewConfig;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;

final class MailableNotice extends Notification
{
    public static function previewConfig(): PreviewConfig
    {
        return PreviewConfig::configure()->withNotifiable(
            fn (): Recipient => new Recipient('Ana', 'es'),
        );
    }

    public function toMail(Recipient $notifiable): Mailable
    {
        return new TextReceipt()->locale('de');
    }
}
