<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Http\Controllers;

use HosmelQ\MessagePreview\Http\Resources\FingerprintResource;
use HosmelQ\MessagePreview\Support\PreviewFingerprint;

final readonly class FingerprintController
{
    public function show(PreviewFingerprint $fingerprint): FingerprintResource
    {
        return new FingerprintResource($fingerprint->generate());
    }
}
