<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Tests\Fixtures\Messages;

use HosmelQ\MessagePreview\Support\PreviewConfig;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class MailNotice extends Notification implements ShouldQueue
{
    public function __construct(
        public string $name,
        null|string $locale = null,
        null|string $notificationId = null,
    ) {
        $this->locale = $locale;
        $this->id = $notificationId;
    }

    public static function previewConfig(): PreviewConfig
    {
        return PreviewConfig::configure()
            ->withMessage(fn (): self => new self('Ana'))
            ->withNotifiable(
                fn (self $notification): Recipient => new Recipient($notification->name, 'es'),
            )
            ->withVariant('explicit-locale', fn (): self => new self('Mateo', 'fr'))
            ->withVariant(
                'app-locale',
                fn (): self => new self('Luca'),
                fn (self $notification): AnonymousNotifiable
                    => new AnonymousNotifiable()->route(
                        'mail',
                        mb_strtolower($notification->name).'@example.test',
                    ),
            )
            ->withVariant(
                'existing-id',
                fn (): self => new self('Ana', notificationId: 'existing-notification-id'),
            )
            ->withVariant('empty-id', fn (): self => new self('Ana', notificationId: ''));
    }

    public function toMail(object $notifiable): MailMessage
    {
        return new MailMessage()->view('workbench::fixtures.notification', ['name' => $this->name]);
    }
}
