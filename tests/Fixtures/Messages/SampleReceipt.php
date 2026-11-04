<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Tests\Fixtures\Messages;

use HosmelQ\MessagePreview\Support\PreviewConfig;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;

final class SampleReceipt extends Mailable
{
    public function __construct(public string $name) {}

    public static function previewConfig(): PreviewConfig
    {
        $factory = fn (): self => new self(fake()->name());

        return PreviewConfig::configure()->withMessage($factory)->withVariant('other', $factory);
    }

    public function content(): Content
    {
        return new Content(htmlString: '<p>'.$this->name.': '.fake()->sentence().'</p>');
    }
}
