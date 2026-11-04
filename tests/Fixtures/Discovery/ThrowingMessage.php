<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Tests\Fixtures\Discovery;

use Illuminate\Mail\Mailable;
use RuntimeException;

if (class_exists(Mailable::class)) {
    throw new RuntimeException('Autoload fixture failed.');
}

if (! class_exists(ThrowingMessage::class, false)) {
    final class ThrowingMessage extends Mailable {}
}
