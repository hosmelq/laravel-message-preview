<?php

declare(strict_types=1);

use HosmelQ\MessagePreview\Discovery\MessageDiscovery;
use HosmelQ\MessagePreview\Tests\Fixtures\Discovery\ConcreteMail;
use HosmelQ\MessagePreview\Tests\Fixtures\Discovery\ConcreteNotification;
use HosmelQ\MessagePreview\Tests\Fixtures\Discovery\InheritedNotification;
use HosmelQ\MessagePreview\Tests\Fixtures\Discovery\ThrowingMessage;
use HosmelQ\MessagePreview\Tests\Fixtures\Registered\RegisteredNotice;

it('reports directories it cannot scan and classes that fail to load', function (): void {
    $discovered = new MessageDiscovery()->discover([
        __DIR__.'/missing',
        __DIR__.'/../Fixtures/Discovery',
    ]);

    expect($discovered['classes'])->toHaveCount(3)
        ->and($discovered['errors'])->toHaveCount(2)
        ->and($discovered['errors'][0])->toStartWith(__DIR__.'/missing: Could not scan for classes')
        ->and($discovered['errors'][1])->toBe(ThrowingMessage::class.': Autoload fixture failed.');
});

it('discovers instantiable mailables and notifications with a public toMail method', function (): void {
    $discovered = new MessageDiscovery()->discover(
        [__DIR__.'/../Fixtures/Discovery'],
        [RegisteredNotice::class],
    );

    expect($discovered['classes'])->toBe([
        ConcreteMail::class,
        ConcreteNotification::class,
        InheritedNotification::class,
        RegisteredNotice::class,
    ]);
});
