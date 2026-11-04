<?php

declare(strict_types=1);

namespace Workbench\App\Mail\Orders;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class OrderDocuments extends Mailable
{
    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return [
            $this->document('invoice-SG-2048.pdf', 'application/pdf', 48_000),
            $this->document('packing-slip-SG-2048.pdf', 'application/pdf', 21_500),
            $this->document('return-label-SG-2048.pdf', 'application/pdf', 96_000),
            $this->document(
                'warranty-and-care-instructions-for-the-linen-collection.pdf',
                'application/pdf',
                312_000,
            ),
            $this->document('product-photos.zip', 'application/zip', 640_000),
            $this->document('size-guide.csv', 'text/csv', 1_800),
        ];
    }

    public function content(): Content
    {
        return new Content(markdown: 'workbench::emails.orders.documents');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address('ana@example.test', 'Ana')],
            subject: 'Your documents for order SG-2048',
        );
    }

    private function document(string $name, string $mime, int $size): Attachment
    {
        return Attachment::fromData(static fn (): string => str_repeat('0', $size), $name)
            ->withMime($mime);
    }
}
