<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Tests\Fixtures\Messages;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;

final class RichReceipt extends Mailable
{
    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn (): string => 'Receipt PDF bytes', 'receipt.pdf')
                ->withMime('application/pdf'),
            Attachment::fromData(fn (): string => 'Résumé', 'note.txt')->withMime('text/plain'),
        ];
    }

    public function content(): Content
    {
        return new Content(markdown: 'workbench::fixtures.receipt');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('orders@example.test', 'Orders'),
            to: [new Address('ana@example.test', 'Ana'), new Address('mateo@example.test')],
            cc: [new Address('finance@example.test', 'Finance')],
            bcc: [new Address('archive@example.test')],
            replyTo: [new Address('help@example.test', 'Support')],
            subject: 'Receipt SG-2048',
        );
    }

    public function headers(): Headers
    {
        return new Headers(text: ['X-Receipt' => 'SG-2048']);
    }
}
