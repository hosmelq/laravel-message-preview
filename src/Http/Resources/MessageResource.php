<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Http\Resources;

use HosmelQ\MessagePreview\ConfiguredMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Str;
use Override;
use ReflectionClass;

/**
 * @property ConfiguredMessage $resource
 */
final class MessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'class_name' => $this->resource->className,
            'error' => $this->resource->error?->getMessage(),
            'key' => $this->resource->key,
            'kind' => is_subclass_of($this->resource->className, Mailable::class)
                ? 'mailable'
                : 'notification',
            'name' => class_basename($this->resource->className),
            'namespace' => new ReflectionClass($this->resource->className)->getNamespaceName(),
            'variants' => array_map(static fn (string $variant): array => [
                'key' => $variant,
                'label' => Str::headline($variant),
            ], $this->resource->config->getVariantNames()),
        ];
    }
}
