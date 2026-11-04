<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Links;

final readonly class UrlCheck
{
    public function __construct(
        public string $status,
        public null|int $httpStatus = null,
        public null|string $finalUrl = null,
        public null|int $contentLength = null,
        public null|string $reason = null,
        public null|string $routeName = null,
    ) {}
}
