<?php

declare(strict_types=1);

namespace Workbench\App\Mail\Orders;

use function Safe\file_get_contents;

use HosmelQ\MessagePreview\Support\PreviewConfig;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class OrderReceipt extends Mailable
{
    public function __construct(public string $customerName, public null|string $note = null) {}

    public static function previewConfig(): PreviewConfig
    {
        return PreviewConfig::configure()
            ->withMessage(
                fn (): self
                    => new self('Ana', 'We will include a handwritten gift note with your order.'),
            )
            ->withVariant(
                'without-note',
                fn (): self => new self('Ana'),
            );
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return [Attachment::fromData(
            fn (): string => file_get_contents(__DIR__.'/../../../resources/receipt.pdf'),
            'receipt-SG-2048.pdf',
        )->withMime('application/pdf')];
    }

    public function content(): Content
    {
        return new Content(view: 'workbench::emails.orders.receipt');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address('ana@example.test', 'Ana')],
            replyTo: [new Address('orders@studio.test', 'Studio Goods Orders')],
            subject: 'Your order SG-2048 is confirmed',
        );
    }
}
