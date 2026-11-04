<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Tests\Fixtures\Messages;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;

final class InlineReceipt extends Mailable
{
    public function content(): Content
    {
        return new Content(view: 'workbench::fixtures.inline');
    }
}
