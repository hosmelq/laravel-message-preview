<?php

declare(strict_types=1);

use HosmelQ\MessagePreview\Support\PreviewFingerprint;
use Illuminate\Http\Resources\Json\JsonResource;

it('keeps the data wrapper when the application disables resource wrapping', function (): void {
    JsonResource::withoutWrapping();

    $this->getJson(route('message-preview.api.fingerprint'))
        ->assertOk()
        ->assertJsonStructure(['data' => ['fingerprint']]);
});

it('returns the fingerprint of the watched files', function (): void {
    $this->getJson(route('message-preview.api.fingerprint'))
        ->assertOk()
        ->assertExactJson(['data' => ['fingerprint' => new PreviewFingerprint()->generate()]]);
});
