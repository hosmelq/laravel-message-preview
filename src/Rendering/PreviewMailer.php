<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Rendering;

use function Illuminate\Support\enum_value;
use function Safe\stream_get_contents;

use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Mail\Mailer;
use LogicException;
use Override;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mime\Email;
use UnitEnum;

final class PreviewMailer extends Mailer implements MailFactory
{
    private null|RenderedMessage $captured = null;

    public function __construct(Factory $views, private readonly PreviewTranslator $translator)
    {
        parent::__construct('message-preview', $views, new NullTransport());
    }

    public function captured(): RenderedMessage
    {
        return $this->captured ?? throw new LogicException(
            "No email was captured. Configure a mail route with new AnonymousNotifiable()->route('mail', ...) or use the Notifiable trait with an email address.",
        );
    }

    /**
     * @param null|string|UnitEnum $name
     */
    #[Override]
    public function mailer($name = null): self
    {
        $mailer = enum_value($name);
        $config = config()->array(
            'mail.mailers.'.(is_string($mailer) ? $mailer : config()->string('mail.default')),
        );

        foreach (['from', 'reply_to'] as $type) {
            $address = $config[$type] ?? config('mail.'.$type);

            if (! is_array($address) || ! is_string($address['address'] ?? null)) {
                continue;
            }

            $name = is_string($address['name'] ?? null) ? $address['name'] : null;

            if ($type === 'from') {
                $this->alwaysFrom($address['address'], $name);
            } else {
                $this->alwaysReplyTo($address['address'], $name);
            }
        }

        return $this;
    }

    #[Override]
    protected function sendSymfonyMessage(Email $message): null
    {
        $html = $this->body($message->getHtmlBody());
        $locale = app()->getLocale();
        $this->captured = new RenderedMessage(
            $message,
            $html === null
                ? null
                : $this->replaceEmbeddedAttachments($html, $message->getAttachments()),
            $html === null ? null : mb_strlen($html, '8bit'),
            $this->body($message->getTextBody()),
            $locale,
            $this->translator->missingReplacements($locale),
            $this->translator->missingTranslations($locale),
        );

        return null;
    }

    private function body(mixed $body): null|string
    {
        if (is_resource($body)) {
            $body = stream_get_contents($body, offset: 0);
        }

        return is_string($body) && filled($body) ? $body : null;
    }
}
