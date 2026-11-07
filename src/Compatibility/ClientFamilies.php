<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Compatibility;

use HosmelQ\CanIEmail\CanIEmail;

final readonly class ClientFamilies
{
    public function __construct(private CanIEmail $canIEmail) {}

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        $families = [];

        foreach ($this->canIEmail->clients() as $client) {
            $families[$client->family] = $client->familyName;
        }

        return $families;
    }
}
