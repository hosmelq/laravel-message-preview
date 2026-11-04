<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Tests\Fixtures\Discovery;

use Illuminate\Notifications\Notification;

abstract class AbstractNotification extends Notification
{
    abstract public function toMail(): object;
}
