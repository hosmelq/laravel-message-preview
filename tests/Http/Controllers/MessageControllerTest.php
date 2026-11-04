<?php

declare(strict_types=1);

use HosmelQ\MessagePreview\Tests\Fixtures\Messages\BrokenConfiguration;
use HosmelQ\MessagePreview\Tests\Fixtures\Messages\LazyNotice;
use HosmelQ\MessagePreview\Tests\Fixtures\Messages\TextReceipt;
use Illuminate\Http\Resources\Json\JsonResource;

beforeEach(function (): void {
    config(['message-preview.discovery.paths' => [__DIR__.'/../../Fixtures/Messages']]);
});

it('marks messages whose configuration fails with the error', function (): void {
    $messages = collect(
        $this->getJson(route('message-preview.api.messages'))->assertOk()->json('data'),
    );

    expect($messages->firstWhere('class_name', BrokenConfiguration::class))->toMatchArray([
        'error' => 'The preview configuration is unavailable.',
        'variants' => [['key' => 'default', 'label' => 'Default']],
    ]);
});

it('reports the directories discovery could not scan', function (): void {
    config(['message-preview.discovery.paths' => [__DIR__.'/missing']]);

    $this->getJson(route('message-preview.api.messages'))
        ->assertOk()
        ->assertJsonPath('data', [])
        ->assertJsonPath('meta.discovery_errors.0', fn (string $error): bool => str_starts_with(
            $error,
            __DIR__.'/missing: Could not scan for classes',
        ));
});

it('keeps the data wrapper when the application disables resource wrapping', function (): void {
    JsonResource::withoutWrapping();

    $this->getJson(route('message-preview.api.messages'))
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['discovery_errors', 'locales']]);
});

it('lists the messages without building any of them', function (): void {
    $response = $this->getJson(route('message-preview.api.messages'))
        ->assertOk()
        ->assertJsonPath('meta', ['discovery_errors' => [], 'locales' => ['en', 'es']]);
    $messages = collect($response->json('data'))->keyBy('class_name');

    expect($messages[LazyNotice::class])->toBe([
        'class_name' => LazyNotice::class,
        'error' => null,
        'key' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.LazyNotice',
        'kind' => 'notification',
        'name' => 'LazyNotice',
        'namespace' => 'HosmelQ\MessagePreview\Tests\Fixtures\Messages',
        'variants' => [
            ['key' => 'default', 'label' => 'Default'],
            ['key' => 'lazy', 'label' => 'Lazy'],
        ],
    ])
        ->and($messages[TextReceipt::class]['kind'])->toBe('mailable');
});
