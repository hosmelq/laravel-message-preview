<?php

declare(strict_types=1);

namespace Workbench\App\Mail\Orders;

use HosmelQ\MessagePreview\Support\PreviewConfig;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class OrderShipped extends Mailable
{
    public function __construct(public int $packages = 1) {}

    public static function previewConfig(): PreviewConfig
    {
        return PreviewConfig::configure()->withVariant(
            'multiple-packages',
            fn (): self => new self(3),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'workbench::emails.orders.shipped');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address('ana@example.test', 'Ana')],
            subject: 'Your order SG-2048 is on its way',
        );
    }
}
