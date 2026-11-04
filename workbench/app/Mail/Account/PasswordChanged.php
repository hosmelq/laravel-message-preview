<?php

declare(strict_types=1);

namespace Workbench\App\Mail\Account;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class PasswordChanged extends Mailable
{
    public function content(): Content
    {
        return new Content(text: 'workbench::emails.account.password-changed');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address('ana@example.test', 'Ana')],
            subject: __('password.subject'),
        );
    }
}
