<?php

declare(strict_types=1);

namespace Workbench\App\Notifications\Account;

use HosmelQ\MessagePreview\Support\PreviewConfig;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class WelcomeNotification extends Notification
{
    public function __construct(public string $name = 'Ana') {}

    public static function previewConfig(): PreviewConfig
    {
        return PreviewConfig::configure()
            ->withNotifiable(
                fn (): AnonymousNotifiable
                    => new AnonymousNotifiable()->route('mail', 'ana@example.test'),
            )
            ->withVariant('new-teammate', fn (): self => new self('Mateo'));
    }

    public function toMail(object $notifiable): MailMessage
    {
        return new MailMessage()
            ->subject('Welcome to Studio Goods')
            ->greeting('Welcome, '.$this->name.'!')
            ->line(
                'Your Studio Goods account is ready. Find your orders and save your everyday favorites in one place.',
            )
            ->action('Open your account', url('/account'))
            ->line('Thanks for joining us.');
    }
}
