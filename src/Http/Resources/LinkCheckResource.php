<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Http\Resources;

use HosmelQ\MessagePreview\Links\UrlCheck;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property array{check: UrlCheck, kinds: list<string>, occurrences: int, url: string} $resource
 */
final class LinkCheckResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'content_length' => $this->resource['check']->contentLength,
            'final_url' => $this->resource['check']->finalUrl,
            'http_status' => $this->resource['check']->httpStatus,
            'kinds' => $this->resource['kinds'],
            'occurrences' => $this->resource['occurrences'],
            'reason' => $this->resource['check']->reason,
            'route_name' => $this->resource['check']->routeName,
            'status' => $this->resource['check']->status,
            'url' => $this->resource['url'],
        ];
    }
}
