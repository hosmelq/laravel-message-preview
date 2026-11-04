<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview;

use HosmelQ\MessagePreview\Support\PreviewConfig;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Throwable;

final readonly class ConfiguredMessage
{
    public string $key;

    /**
     * @param class-string<Mailable|Notification> $className
     */
    public function __construct(
        public string $className,
        public PreviewConfig $config,
        public null|Throwable $error = null,
    ) {
        $this->key = str_replace('\\', '.', $className);
    }
}
