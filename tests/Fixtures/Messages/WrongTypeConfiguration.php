<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Tests\Fixtures\Messages;

use Illuminate\Mail\Mailable;

final class WrongTypeConfiguration extends Mailable
{
    public static function previewConfig(): string
    {
        return 'invalid';
    }
}
