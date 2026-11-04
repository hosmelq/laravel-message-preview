<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Tests\Fixtures\Messages;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;

final class TextReceipt extends Mailable
{
    public function content(): Content
    {
        return new Content(text: 'workbench::fixtures.text');
    }
}
