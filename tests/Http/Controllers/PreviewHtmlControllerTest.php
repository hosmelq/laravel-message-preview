<?php

declare(strict_types=1);

beforeEach(function (): void {
    config(['message-preview.discovery.paths' => [__DIR__.'/../../Fixtures/Messages']]);
});

it('returns not found for a message without HTML', function (): void {
    $this->get(route('message-preview.previews.html', [
        'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.TextReceipt',
        'variant' => 'default',
    ]))->assertNotFound();
});

it('returns the error of a message that fails to render', function (): void {
    $this->get(route('message-preview.previews.html', [
        'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.BrokenConfiguration',
        'variant' => 'default',
    ]))
        ->assertInternalServerError()
        ->assertSee('The preview configuration is unavailable.');
});

it('returns the HTML the preview shows', function (): void {
    $parameters = [
        'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.RichReceipt',
        'variant' => 'default',
    ];
    $html = $this->getJson(route('message-preview.api.previews.show', $parameters))
        ->json('data.html');

    $this->get(route('message-preview.previews.html', $parameters))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
        ->assertContent($html);
});
