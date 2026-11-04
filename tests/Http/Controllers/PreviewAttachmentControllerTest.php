<?php

declare(strict_types=1);

beforeEach(function (): void {
    config(['message-preview.discovery.paths' => [__DIR__.'/../../Fixtures/Messages']]);
});

it('returns not found for unknown attachments', function (string $index): void {
    $this->get(
        '/message-preview/messages/HosmelQ.MessagePreview.Tests.Fixtures.Messages.RichReceipt/previews/default/attachments/'
            .$index,
    )->assertNotFound();
})->with(['-1', '1.5', '2', 'nope']);

it('downloads the original bytes of an attachment', function (
    string $message,
    string $body,
    string $type,
    string $filename,
): void {
    $this->get(route('message-preview.previews.attachment', [
        'index' => 0,
        'message' => $message,
        'variant' => 'default',
    ]))
        ->assertOk()
        ->assertStreamedContent($body)
        ->assertHeader('Content-Type', $type)
        ->assertHeader('Content-Disposition', 'attachment; filename='.$filename);
})->with([
    'attachment' => [
        'HosmelQ.MessagePreview.Tests.Fixtures.Messages.RichReceipt',
        'Receipt PDF bytes',
        'application/pdf',
        'receipt.pdf',
    ],
    'inline image' => [
        'HosmelQ.MessagePreview.Tests.Fixtures.Messages.InlineReceipt',
        'image bytes',
        'image/png',
        'logo.png',
    ],
]);
