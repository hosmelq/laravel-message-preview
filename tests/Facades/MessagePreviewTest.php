<?php

declare(strict_types=1);

use HosmelQ\MessagePreview\Facades\MessagePreview;
use HosmelQ\MessagePreview\MessageCatalog;
use HosmelQ\MessagePreview\Support\PreviewConfig;
use HosmelQ\MessagePreview\Tests\Fixtures\Registered\RegisteredNotice;

it('registers messages through the facade', function (): void {
    MessagePreview::register(
        RegisteredNotice::class,
        fn (): PreviewConfig => PreviewConfig::configure(),
    );

    expect(
        app(MessageCatalog::class)->find(
            'HosmelQ.MessagePreview.Tests.Fixtures.Registered.RegisteredNotice',
            'default',
        ),
    )->not->toBeNull();
});
