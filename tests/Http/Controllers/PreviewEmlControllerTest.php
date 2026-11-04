<?php

declare(strict_types=1);

beforeEach(function (): void {
    config(['message-preview.discovery.paths' => [__DIR__.'/../../Fixtures/Messages']]);
});

it('returns the error of a message that cannot be serialized', function (): void {
    config(['mail.from' => null]);

    $this->get(route('message-preview.previews.eml', [
        'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.InlineReceipt',
        'variant' => 'default',
    ]))
        ->assertInternalServerError()
        ->assertSee('An email must have a');
});

it('downloads the complete MIME message', function (): void {
    $eml = $this->get(route('message-preview.previews.eml', [
        'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.RichReceipt',
        'variant' => 'default',
    ]))
        ->assertOk()
        ->assertHeader('Content-Type', 'message/rfc822')
        ->assertHeader('Content-Disposition', 'attachment; filename=RichReceipt.eml')
        ->streamedContent();

    expect($eml)->toContain(
        'Subject: Receipt SG-2048',
        'Content-Type: application/pdf',
        base64_encode('Receipt PDF bytes'),
        base64_encode('Résumé'),
    );
});

it('keeps inline images as attachments', function (): void {
    $eml = $this->get(route('message-preview.previews.eml', [
        'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.InlineReceipt',
        'variant' => 'default',
    ]))
        ->assertOk()
        ->streamedContent();

    expect($eml)->toContain('cid:', 'Content-Disposition: inline;', base64_encode('image bytes'))
        ->not->toContain('data:image');
});

it('names the download after the message, the variant and the locale', function (): void {
    $this->get(route('message-preview.previews.eml', [
        'locale' => 'es',
        'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.LazyReceipt',
        'variant' => 'without-note',
    ]))
        ->assertOk()
        ->assertHeader(
            'Content-Disposition',
            'attachment; filename=LazyReceipt-without-note-es.eml',
        );
});
