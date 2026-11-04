<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Tests\Fixtures\Registered;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class RegisteredNotice extends Notification
{
    public function __construct(public string $name = 'Ana') {}

    public function toMail(object $notifiable): MailMessage
    {
        return new MailMessage()->subject('Welcome '.$this->name)->line('Your account is ready.');
    }
}
