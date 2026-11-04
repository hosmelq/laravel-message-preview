<?php

declare(strict_types=1);

use HosmelQ\MessagePreview\Http\Resources\FailedPreviewResource;

it('makes the location of the error relative to the application', function (): void {
    app()->setBasePath(dirname(__DIR__, 3));

    $exception = new RuntimeException('Rendering failed.');

    expect(new FailedPreviewResource($exception)->resolve())->toBe([
        'error' => [
            'exception' => RuntimeException::class,
            'file' => 'tests/Http/Resources/FailedPreviewResourceTest.php',
            'line' => $exception->getLine(),
            'message' => 'Rendering failed.',
        ],
        'status' => 'failed',
    ]);
});
