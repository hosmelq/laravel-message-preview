<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Rendering;

use Closure;
use HosmelQ\MessagePreview\ConfiguredMessage;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Contracts\View\Factory;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Localizable;
use Illuminate\Translation\Translator;
use LogicException;
use Throwable;

final readonly class MessageRenderer
{
    use Localizable;

    public function render(
        ConfiguredMessage $message,
        string $variant,
        null|string $locale = null,
    ): RenderedMessage {
        if ($message->error instanceof Throwable) {
            throw $message->error;
        }

        $transaction = config()->boolean('message-preview.database.rollback')
            ? rescue(static function (): array {
                $connection = DB::connection();
                $level = $connection->transactionLevel();

                $connection->beginTransaction();

                return ['connection' => $connection, 'level' => $level];
            }, report: false)
            : null;

        try {
            return $this->translate($message, $variant, $locale);
        } finally {
            if ($transaction !== null) {
                $transaction['connection']->rollBack($transaction['level']);
            }
        }
    }

    private function send(
        ConfiguredMessage $message,
        string $variant,
        null|string $locale,
        PreviewMailer $mailer,
    ): RenderedMessage {
        $factory = $message->config->getMessage($variant);
        $subject = $factory instanceof Closure ? $factory() : new ($message->className)();

        if ($subject instanceof Mailable) {
            if ($locale !== null) {
                $subject->locale($locale);
            }

            $subject->send($mailer);

            return $mailer->captured();
        }

        if (! $subject instanceof Notification) {
            throw new LogicException(
                'The preview factory must return a mailable or a notification.',
            );
        }

        $notifiable = $message->config->getNotifiable($variant) ?? throw new LogicException(
            "Configure withNotifiable() with new AnonymousNotifiable()->route('mail', ...) or use the Notifiable trait with an email address.",
        );
        $recipient = $notifiable($subject);
        $preferredLocale = $recipient instanceof HasLocalePreference
            ? $recipient->preferredLocale()
            : null;

        if (blank($subject->id)) {
            $subject->id = (string) Str::uuid();
        }

        return $this->withLocale(
            $locale ?? $subject->locale ?? $preferredLocale,
            static function () use ($locale, $mailer, $recipient, $subject): RenderedMessage {
                new PreviewMailChannel($mailer, app(Markdown::class), $locale)->send(
                    $recipient,
                    $subject,
                );

                return $mailer->captured();
            },
        );
    }

    private function translate(
        ConfiguredMessage $message,
        string $variant,
        null|string $locale,
    ): RenderedMessage {
        $translator = app(Translator::class);
        $preview = PreviewTranslator::copy($translator);

        Lang::swap($preview);

        mt_srand(crc32($message->key.'|'.$variant));

        try {
            return $this->withLocale(
                $locale ?? app()->getLocale(),
                fn (): RenderedMessage => $this->send(
                    $message,
                    $variant,
                    $locale,
                    new PreviewMailer(app(Factory::class), $preview),
                ),
            );
        } finally {
            mt_srand();

            Lang::swap($translator);
        }
    }
}
