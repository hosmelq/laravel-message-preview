<?php

declare(strict_types=1);

namespace Workbench\App\Mail\Marketing;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class BrokenNewsletter extends Mailable
{
    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                static fn (): string => "sku,price\nSG-101,24.00\nSG-102,38.00\n",
                'price-list.csv',
            )->withMime('text/csv'),
        ];
    }

    public function content(): Content
    {
        return new Content(
            view: 'workbench::emails.marketing.broken-newsletter',
            text: 'workbench::emails.marketing.broken-newsletter-text',
            with: [
                'icon' => $this->swatch('#292524', 20, 20),
                'legal' => str_repeat(
                    'Prices and availability may change without notice, and some products ship only within the United States. ',
                    1_000,
                ),
                'products' => [
                    ['image' => $this->swatch('#d6c7a8', 260, 160), 'name' => 'Everyday notebook'],
                    ['image' => $this->swatch('#8a8f6a', 260, 160), 'name' => 'Canvas tote'],
                ],
            ],
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address('ana@example.test', 'Ana')],
            subject: __('newsletter.subject'),
        );
    }

    private function swatch(string $color, int $width, int $height): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode(
            "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{$width}\" height=\"{$height}\"><rect width=\"100%\" height=\"100%\" fill=\"{$color}\"/></svg>",
        );
    }
}
