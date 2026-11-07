<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Http\Resources;

use HosmelQ\CanIEmail\Feature;
use HosmelQ\MessagePreview\Compatibility\ClientSupport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property array{clients: list<ClientSupport>, feature: Feature} $resource
 */
final class CompatibilityIssueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'clients' => array_map(static fn (ClientSupport $client): array => [
                'id' => $client->id,
                'level' => mb_strtolower($client->level->name),
                'limited' => $client->limited,
                'missing' => $client->missing,
                'name' => $client->name,
                'notes' => $client->notes,
                'unknown' => $client->unknown,
            ], $this->resource['clients']),
            'title' => $this->resource['feature']->title,
            'url' => $this->resource['feature']->url,
        ];
    }
}
