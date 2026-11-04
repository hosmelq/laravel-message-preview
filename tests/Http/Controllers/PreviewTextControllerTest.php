<?php

declare(strict_types=1);

beforeEach(function (): void {
    config(['message-preview.discovery.paths' => [__DIR__.'/../../Fixtures/Messages']]);
});

it('returns not found for a message without plain text', function (): void {
    $this->get(route('message-preview.previews.text', [
        'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.InlineReceipt',
        'variant' => 'default',
    ]))->assertNotFound();
});

it('returns the plain text without escaping it', function (): void {
    $this->get(route('message-preview.previews.text', [
        'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.TextReceipt',
        'variant' => 'default',
    ]))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('<strong>Literal text</strong>', false);
});
