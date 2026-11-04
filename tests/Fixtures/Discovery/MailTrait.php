<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Tests\Fixtures\Discovery;

use stdClass;

trait MailTrait
{
    public function toMail(): object
    {
        return new stdClass();
    }
}
