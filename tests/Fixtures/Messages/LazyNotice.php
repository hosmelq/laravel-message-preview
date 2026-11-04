<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Tests\Fixtures\Messages;

use HosmelQ\MessagePreview\Support\PreviewConfig;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use RuntimeException;

final class LazyNotice extends Notification
{
    public static function previewConfig(): PreviewConfig
    {
        return PreviewConfig::configure()
            ->withMessage(fn (): never => throw new RuntimeException('Default message resolved.'))
            ->withNotifiable(
                fn (): never => throw new RuntimeException('Default recipient resolved.'),
            )
            ->withVariant(
                'lazy',
                fn (): never => throw new RuntimeException('Variant message resolved.'),
                fn (): never => throw new RuntimeException('Variant recipient resolved.'),
            );
    }

    public function toMail(object $notifiable): MailMessage
    {
        return new MailMessage();
    }
}
