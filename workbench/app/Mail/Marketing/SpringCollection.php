<?php

declare(strict_types=1);

namespace Workbench\App\Mail\Marketing;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class SpringCollection extends Mailable
{
    public function content(): Content
    {
        return new Content(
            view: 'workbench::emails.marketing.spring-collection',
            with: [
                'icon' => $this->swatch('#292524', 20, 20),
                'products' => [
                    [
                        'color' => 'Sand',
                        'image' => $this->swatch('#d6c7a8', 236, 160),
                        'name' => 'Everyday notebook',
                        'price' => '$24.00',
                    ],
                    [
                        'color' => 'Olive',
                        'image' => $this->swatch('#8a8f6a', 236, 160),
                        'name' => 'Canvas tote',
                        'price' => '$38.00',
                    ],
                    [
                        'color' => 'Clay',
                        'image' => $this->swatch('#c08a6b', 236, 160),
                        'name' => 'Ceramic mug',
                        'price' => '$18.00',
                    ],
                    [
                        'color' => 'Stone',
                        'image' => $this->swatch('#a8a29e', 236, 160),
                        'name' => 'Linen napkins',
                        'price' => '$22.00',
                    ],
                ],
            ],
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address('ana@example.test', 'Ana')],
            subject: 'The spring collection is here',
        );
    }

    private function swatch(string $color, int $width, int $height): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode(
            "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{$width}\" height=\"{$height}\"><rect width=\"100%\" height=\"100%\" fill=\"{$color}\"/></svg>",
        );
    }
}
