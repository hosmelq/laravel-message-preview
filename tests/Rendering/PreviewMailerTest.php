<?php

declare(strict_types=1);

use HosmelQ\MessagePreview\Rendering\PreviewMailer;
use HosmelQ\MessagePreview\Rendering\PreviewTranslator;
use HosmelQ\MessagePreview\Tests\Fixtures\Messages\InlineReceipt;
use HosmelQ\MessagePreview\Tests\Fixtures\Messages\TextReceipt;
use Illuminate\Contracts\View\Factory;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Translation\Translator;
use Symfony\Component\Mime\Email;

beforeEach(function (): void {
    $this->mailer = new PreviewMailer(
        app(Factory::class),
        PreviewTranslator::copy(app(Translator::class)),
    );
});

it('captures an HTML body given as a stream without emptying it', function (): void {
    new TextReceipt()->withSymfonyMessage(function (Email $email): void {
        $email->html(fopen('data://text/plain,<p>Streamed body</p>', 'rb'));
    })->send($this->mailer);

    expect($this->mailer->captured()->html)->toBe('<p>Streamed body</p>')
        ->and($this->mailer->captured()->email->toString())->toContain('<p>Streamed body</p>');
});

it('replaces inline images with data URIs and measures the HTML that is sent', function (): void {
    new InlineReceipt()->send($this->mailer);

    $rendered = $this->mailer->captured();

    expect($rendered->html)->toContain('data:image/png;base64,'.base64_encode('image bytes'))
        ->not->toContain('cid:')
        ->and($rendered->htmlSize)->toBe(mb_strlen($rendered->email->getHtmlBody(), '8bit'));
});

it('reports blank bodies as null', function (): void {
    new class () extends Mailable {
        public function content(): Content
        {
            return new Content(htmlString: " \n\t");
        }
    }->send($this->mailer);

    expect($this->mailer->captured())->html->toBeNull()->text->toBeNull();
});

it('uses the sender and reply-to address of the mailer a message asks for', function (): void {
    config([
        'mail.from' => ['address' => 'hello@studio.test', 'name' => 'Studio Goods'],
        'mail.mailers.marketing' => [
            'from' => ['address' => 'news@studio.test', 'name' => 'Studio News'],
            'reply_to' => ['address' => 'replies@studio.test'],
        ],
        'mail.reply_to' => ['address' => 'help@studio.test', 'name' => 'Studio Help'],
    ]);

    new TextReceipt()->mailer('marketing')->send($this->mailer);

    $marketing = $this->mailer->captured()->email;

    new TextReceipt()->send($this->mailer);

    $default = $this->mailer->captured()->email;

    expect($marketing->getFrom()[0]->getAddress())->toBe('news@studio.test')
        ->and($marketing->getReplyTo()[0]->getAddress())->toBe('replies@studio.test')
        ->and($default->getFrom()[0]->getAddress())->toBe('hello@studio.test')
        ->and($default->getReplyTo()[0]->getAddress())->toBe('help@studio.test');
});
