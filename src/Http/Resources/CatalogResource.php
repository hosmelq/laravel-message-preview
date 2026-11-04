<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Http\Resources;

use HosmelQ\MessagePreview\ConfiguredMessage;
use HosmelQ\MessagePreview\Support\PreviewLocales;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property array{errors: list<string>, messages: list<ConfiguredMessage>} $resource
 */
final class CatalogResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): AnonymousResourceCollection
    {
        return MessageResource::collection($this->resource['messages']);
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'discovery_errors' => $this->resource['errors'],
                'locales' => new PreviewLocales()->all(),
            ],
        ];
    }
}
