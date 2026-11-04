<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Tests\Fixtures\Messages;

use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Notifications\Notification;

final readonly class Recipient implements HasLocalePreference
{
    public function __construct(public string $name, private string $locale) {}

    public function preferredLocale(): string
    {
        return $this->locale;
    }

    public function routeNotificationFor(string $driver, Notification $notification): string
    {
        return 'ana@example.test';
    }
}
