<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use Override;
use Throwable;

/**
 * @property Throwable $resource
 */
final class FailedPreviewResource extends JsonResource
{
    public static $wrap = 'data';

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'error' => [
                'exception' => $this->resource::class,
                'file' => Str::chopStart(
                    $this->resource->getFile(),
                    base_path().DIRECTORY_SEPARATOR,
                ),
                'line' => $this->resource->getLine(),
                'message' => $this->resource->getMessage(),
            ],
            'status' => 'failed',
        ];
    }
}
