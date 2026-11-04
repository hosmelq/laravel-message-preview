<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Facades;

use Closure;
use HosmelQ\MessagePreview\MessageCatalog;
use HosmelQ\MessagePreview\Support\PreviewConfig;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static void register(class-string<Mailable|Notification> $className, Closure(): PreviewConfig $config)
 */
final class MessagePreview extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return MessageCatalog::class;
    }
}
