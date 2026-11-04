<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Tests\Fixtures\Discovery;

use Illuminate\Notifications\Notification;
use stdClass;

class ProtectedNotification extends Notification
{
    protected function toMail(): stdClass
    {
        return new stdClass();
    }
}
