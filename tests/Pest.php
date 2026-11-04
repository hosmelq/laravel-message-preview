<?php

declare(strict_types=1);

use HosmelQ\MessagePreview\Tests\TestCase;
use Illuminate\Support\Facades\Http;

pest()->extend(TestCase::class)
    ->beforeEach(function (): void {
        Http::preventStrayRequests();
    })
    ->in(__DIR__);
