<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property string $resource
 */
final class FingerprintResource extends JsonResource
{
    public static $wrap = 'data';

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'fingerprint' => $this->resource,
        ];
    }
}
