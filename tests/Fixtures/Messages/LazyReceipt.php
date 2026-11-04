<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Tests\Fixtures\Messages;

use HosmelQ\MessagePreview\Support\PreviewConfig;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use RuntimeException;

final class LazyReceipt extends Mailable
{
    public static function previewConfig(): PreviewConfig
    {
        return PreviewConfig::configure()
            ->withMessage(
                fn (): never => throw new RuntimeException('Default fixture is unavailable.'),
            )
            ->withVariant('without-note', fn (): self => new self());
    }

    public function content(): Content
    {
        return new Content(htmlString: '<h1>Receipt</h1><p>No note</p>');
    }
}
