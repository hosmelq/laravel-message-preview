<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Tests\Fixtures\Messages;

use HosmelQ\MessagePreview\Support\PreviewConfig;
use Illuminate\Mail\Mailable;
use RuntimeException;

final class BrokenConfiguration extends Mailable
{
    public static function previewConfig(): PreviewConfig
    {
        throw new RuntimeException('The preview configuration is unavailable.');
    }
}
