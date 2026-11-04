<?php

declare(strict_types=1);

use HosmelQ\MessagePreview\MessageCatalog;
use HosmelQ\MessagePreview\Support\PreviewConfig;
use HosmelQ\MessagePreview\Tests\Fixtures\Messages\BrokenConfiguration;
use HosmelQ\MessagePreview\Tests\Fixtures\Messages\MailNotice;
use HosmelQ\MessagePreview\Tests\Fixtures\Messages\WrongTypeConfiguration;
use HosmelQ\MessagePreview\Tests\Fixtures\Registered\RegisteredNotice;

beforeEach(function (): void {
    config(['message-preview.discovery.paths' => [__DIR__.'/Fixtures/Messages']]);
});

it('keeps a message whose configuration fails with the error and the default variant', function (
    string $className,
    string $error,
): void {
    $message = app(MessageCatalog::class)->find(str_replace('\\', '.', $className), 'default');

    expect($message->error->getMessage())->toBe($error)
        ->and($message->config->getVariantNames())->toBe(['default']);
})->with([
    'throwing' => [BrokenConfiguration::class, 'The preview configuration is unavailable.'],
    'wrong type' => [
        WrongTypeConfiguration::class,
        WrongTypeConfiguration::class.'::previewConfig() must return a '.PreviewConfig::class.'.',
    ],
]);

it('keeps a registered message whose configuration fails with the error', function (): void {
    app(MessageCatalog::class)->register(
        RegisteredNotice::class,
        fn (): PreviewConfig
            => throw new RuntimeException('The registered configuration is unavailable.'),
    );

    $message = app(MessageCatalog::class)->find(
        'HosmelQ.MessagePreview.Tests.Fixtures.Registered.RegisteredNotice',
        'default',
    );

    expect($message->error->getMessage())->toBe('The registered configuration is unavailable.');
});

it('returns null for unknown messages and variants', function (string $key, string $variant): void {
    expect(app(MessageCatalog::class)->find($key, $variant))->toBeNull();
})->with([
    'unknown message' => ['Unknown.Message', 'default'],
    'unknown variant' => ['HosmelQ.MessagePreview.Tests.Fixtures.Messages.TextReceipt', 'missing'],
]);

it('finds messages registered outside the configured paths', function (): void {
    app(MessageCatalog::class)->register(
        RegisteredNotice::class,
        fn (): PreviewConfig => PreviewConfig::configure()->withVariant(
            'mateo',
            fn (): RegisteredNotice => new RegisteredNotice('Mateo'),
        ),
    );

    $message = app(MessageCatalog::class)->find(
        'HosmelQ.MessagePreview.Tests.Fixtures.Registered.RegisteredNotice',
        'mateo',
    );

    expect($message->className)->toBe(RegisteredNotice::class);
});

it('lists the discovered messages with the configuration from their previewConfig() method', function (): void {
    $messages = collect(app(MessageCatalog::class)->all()['messages'])->keyBy('className');

    expect($messages)->toHaveKeys([BrokenConfiguration::class, MailNotice::class])
        ->and($messages[MailNotice::class]->config->getVariantNames())
        ->toBe(['default', 'explicit-locale', 'app-locale', 'existing-id', 'empty-id']);
});

it('uses a registered configuration in place of the previewConfig() method', function (): void {
    app(MessageCatalog::class)->register(
        MailNotice::class,
        fn (): PreviewConfig => PreviewConfig::configure(),
    );

    $message = app(MessageCatalog::class)->find(
        'HosmelQ.MessagePreview.Tests.Fixtures.Messages.MailNotice',
        'default',
    );

    expect($message->config->getVariantNames())->toBe(['default']);
});
