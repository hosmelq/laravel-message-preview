<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;
use Symfony\Component\Mime\Address;

/**
 * @property Address $resource
 */
final class AddressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'address' => $this->resource->getAddress(),
            'name' => $this->resource->getName() === '' ? null : $this->resource->getName(),
        ];
    }
}
