<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Tests\Fixtures\Messages;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;

final class TranslatedReceipt extends Mailable
{
    public function content(): Content
    {
        return new Content(
            htmlString: '<p>'.__('messages.absent').'</p><p>'.__('Signed in as :email').'</p>',
        );
    }
}
