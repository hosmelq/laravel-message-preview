<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Tests\Fixtures\Discovery;

use Illuminate\Notifications\Notification;
use stdClass;

final class ConcreteNotification extends Notification
{
    public function toMail(): stdClass
    {
        return new stdClass();
    }
}
