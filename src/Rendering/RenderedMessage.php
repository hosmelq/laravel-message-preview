<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Rendering;

use Symfony\Component\Mime\Email;

final readonly class RenderedMessage
{
    /**
     * @param list<array{key: string, lines: list<string>, placeholder: string}> $missingReplacements
     * @param list<array{key: string, lines: list<string>}> $missingTranslations
     */
    public function __construct(
        public Email $email,
        public null|string $html,
        public null|int $htmlSize,
        public null|string $text,
        public string $locale,
        public array $missingReplacements,
        public array $missingTranslations,
    ) {}
}
