<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Compatibility;

use HosmelQ\CanIEmail\Enums\Support;

final readonly class ClientSupport
{
    /**
     * @param list<string> $limited
     * @param list<string> $missing
     * @param list<string> $notes
     * @param list<string> $unknown
     */
    public function __construct(
        public string $id,
        public Support $level,
        public array $limited,
        public array $missing,
        public string $name,
        public array $notes,
        public array $unknown,
    ) {}
}
