<?php

declare(strict_types=1);

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;

beforeEach(function (): void {
    config([
        'mail.default' => 'primary',
        'mail.mailers' => [
            'alternate' => ['transport' => 'array'],
            'primary' => ['transport' => 'array'],
        ],
        'message-preview.discovery.paths' => [__DIR__.'/../../Fixtures/Messages'],
    ]);

    Event::fake([
        MessageSending::class,
        MessageSent::class,
        NotificationSending::class,
        NotificationSent::class,
    ]);
});

it('rejects invalid input without sending', function (array $input): void {
    $this->postJson(route('message-preview.api.previews.send', [
        'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.RichReceipt',
        'variant' => 'default',
    ]), ['to' => 'tester@example.test', ...$input])
        ->assertUnprocessable()
        ->assertJsonStructure(['errors', 'message']);

    expect(Mail::mailer('primary')->getSymfonyTransport()->messages())->toBeEmpty();
})->with([
    'array locale' => [['locale' => ['en']]],
    'array mailer' => [['mailer' => ['primary']]],
    'invalid recipient' => [['to' => 'invalid']],
    'missing recipient' => [['to' => null]],
    'multiple recipients' => [['to' => ['one@example.test', 'two@example.test']]],
    'unknown locale' => [['locale' => 'unknown']],
    'unknown mailer' => [['mailer' => 'missing']],
]);

it('returns not found for unknown messages', function (): void {
    $this->postJson(route('message-preview.api.previews.send', [
        'message' => 'Unknown.Message',
        'variant' => 'default',
    ]), ['to' => 'tester@example.test'])->assertNotFound();
});

it('returns render failures as server errors', function (): void {
    $this->postJson(route('message-preview.api.previews.send', [
        'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.LazyReceipt',
        'variant' => 'default',
    ]), ['to' => 'tester@example.test'])
        ->assertInternalServerError()
        ->assertJsonPath('message', 'Default fixture is unavailable.');
});

it('returns transport failures as validation errors', function (): void {
    $transport = Mockery::mock(TransportInterface::class);

    $transport
        ->shouldReceive('send')
        ->once()
        ->andThrow(new TransportException('SMTP unavailable.'));

    Mail::mailer('primary')->setSymfonyTransport($transport);

    $this->postJson(route('message-preview.api.previews.send', [
        'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.RichReceipt',
        'variant' => 'default',
    ]), ['to' => 'tester@example.test'])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'SMTP unavailable.');
});

it('sends notifications in the selected variant and locale', function (): void {
    $this->postJson(route('message-preview.api.previews.send', [
        'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.MailNotice',
        'variant' => 'explicit-locale',
    ]), ['locale' => 'en', 'to' => 'tester@example.test'])->assertNoContent();

    $email = Mail::mailer('primary')
        ->getSymfonyTransport()
        ->messages()
        ->sole()
        ->getOriginalMessage();

    expect($email->getHtmlBody())->toContain('Mateo:en');
});

it('sends only to the test recipient through the selected mailer without mail or notification events', function (): void {
    config(['mail.to' => ['address' => 'override@example.test', 'name' => 'Override']]);

    $this->postJson(route('message-preview.api.previews.send', [
        'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.RichReceipt',
        'variant' => 'default',
    ]), ['mailer' => 'alternate', 'to' => 'tester@example.test'])->assertNoContent();

    $sent = Mail::mailer('alternate')->getSymfonyTransport()->messages()->sole();
    $email = $sent->getOriginalMessage();

    expect($sent->getEnvelope()->getRecipients())->toEqual([new Address('tester@example.test')])
        ->and($email->getTo())->toEqual([new Address('tester@example.test')])
        ->and($email->getCc())->toBe([])
        ->and($email->getBcc())->toBe([])
        ->and($email->getSubject())->toBe('Receipt SG-2048')
        ->and($email->getAttachments())->toHaveCount(2)
        ->and(Mail::mailer('primary')->getSymfonyTransport()->messages())->toBeEmpty();

    Event::assertNotDispatched(MessageSending::class);
    Event::assertNotDispatched(MessageSent::class);
    Event::assertNotDispatched(NotificationSending::class);
    Event::assertNotDispatched(NotificationSent::class);
});

it('sends the sample data the preview shows', function (): void {
    $parameters = [
        'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.SampleReceipt',
        'variant' => 'other',
    ];
    $html = $this->getJson(
        route('message-preview.api.previews.show', [...$parameters, 'locale' => 'es']),
    )->json('data.html');

    $this->postJson(route('message-preview.api.previews.send', $parameters), [
        'locale' => 'es',
        'to' => 'tester@example.test',
    ])->assertNoContent();

    $email = Mail::mailer('primary')
        ->getSymfonyTransport()
        ->messages()
        ->sole()
        ->getOriginalMessage();

    expect($email->getHtmlBody())->toBe($html);
});

it('uses the default mailer when none is selected and keeps inline images attached', function (
    array $input,
): void {
    $this->postJson(route('message-preview.api.previews.send', [
        'message' => 'HosmelQ.MessagePreview.Tests.Fixtures.Messages.InlineReceipt',
        'variant' => 'default',
    ]), ['to' => 'tester@example.test', ...$input])->assertNoContent();

    $email = Mail::mailer('primary')
        ->getSymfonyTransport()
        ->messages()
        ->sole()
        ->getOriginalMessage();

    expect($email->getHtmlBody())->toContain('cid:')->not->toContain('data:image')
        ->and($email->getAttachments()[0]->getDisposition())->toBe('inline');
})->with(['omitted' => [[]], 'null' => [['mailer' => null]]]);
