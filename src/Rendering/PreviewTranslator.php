<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Rendering;

use function Safe\preg_match;
use function Safe\preg_match_all;

use Closure;
use Countable;
use Illuminate\Translation\Translator;
use Override;

final class PreviewTranslator extends Translator
{
    private const string PLACEHOLDER = '/(?<![\w:]):[A-Za-z]\w*/';

    /**
     * @var array<string, array<string, array<string, true>>>
     */
    private array $lines = [];

    /**
     * @var array<string, array<string, true>>
     */
    private array $missing = [];

    /**
     * @var array<string, array<string, array{key: string, placeholder: string}>>
     */
    private array $missingReplacements = [];

    private bool $recording = true;

    public static function copy(Translator $translator): self
    {
        $preview = new self($translator->loader, $translator->locale);
        $preview->determineLocalesUsing = $translator->determineLocalesUsing;
        $preview->fallback = $translator->fallback;
        $preview->handleMissingTranslationKeys = $translator->handleMissingTranslationKeys;
        $preview->loaded = $translator->loaded;
        $preview->missingTranslationKeyCallback = $translator->missingTranslationKeyCallback;
        $preview->selector = $translator->selector;
        $preview->stringableHandlers = $translator->stringableHandlers;

        return $preview;
    }

    /**
     * @param string $key
     * @param array<array-key, mixed>|Countable|float|int $number
     * @param array<array-key, mixed> $replace
     * @param null|string $locale
     */
    #[Override]
    public function choice($key, $number, array $replace = [], $locale = null): string
    {
        $line = $this->withoutRecording(
            fn (): string => parent::choice($key, $number, $replace, $locale),
        );

        $this->record($key, $locale, true, $line);

        return $line;
    }

    /**
     * @param string $key
     * @param array<array-key, mixed> $replace
     * @param null|string $locale
     * @param bool $fallback
     *
     * @return array<array-key, mixed>|string
     */
    #[Override]
    public function get($key, array $replace = [], $locale = null, $fallback = true): array|string
    {
        $line = parent::get($key, $replace, $locale, $fallback);

        $this->record($key, $locale, $fallback, $line);

        return $line;
    }

    /**
     * @param string $key
     * @param null|string $locale
     * @param bool $fallback
     */
    #[Override]
    public function has($key, $locale = null, $fallback = true): bool
    {
        return $this->withoutRecording(fn (): bool => parent::has($key, $locale, $fallback));
    }

    /**
     * @return list<array{key: string, lines: list<string>, placeholder: string}>
     */
    public function missingReplacements(string $locale): array
    {
        return array_values(array_map(fn (array $replacement): array => [
            'key' => $replacement['key'],
            'lines' => $this->lines($replacement['key'], $locale),
            'placeholder' => $replacement['placeholder'],
        ], $this->missingReplacements[$locale] ?? []));
    }

    /**
     * @return list<array{key: string, lines: list<string>}>
     */
    public function missingTranslations(string $locale): array
    {
        return array_map(fn (string $key): array => [
            'key' => $key,
            'lines' => $this->lines($key, $locale),
        ], array_map(strval(...), array_keys($this->missing[$locale] ?? [])));
    }

    /**
     * @return list<string>
     */
    private function lines(string $key, string $locale): array
    {
        return array_map(strval(...), array_keys($this->lines[$locale][$key] ?? []));
    }

    /**
     * @param array<array-key, mixed>|string $line
     */
    private function record(
        string $key,
        null|string $locale,
        bool $fallback,
        array|string $line,
    ): void {
        if (! $this->recording) {
            return;
        }

        $this->withoutRecording(function () use ($fallback, $key, $line, $locale): void {
            $locale ??= $this->getLocale();

            $this->recordMissingTranslation($key, $locale);

            if (is_string($line) && $line !== '') {
                $this->lines[$locale][$key][$line] = true;

                $this->recordMissingReplacements($key, $locale, $fallback, $line);
            }
        });
    }

    private function recordMissingReplacements(
        string $key,
        string $locale,
        bool $fallback,
        string $line,
    ): void {
        if (preg_match_all(self::PLACEHOLDER, $line, $kept) === 0) {
            return;
        }

        $source = (clone $this)->handleMissingKeysUsing(null)->get($key, [], $locale, $fallback);

        preg_match_all(self::PLACEHOLDER, is_string($source) ? $source : '', $written);

        foreach (array_intersect($kept[0], $written[0]) as $placeholder) {
            $this->missingReplacements[$locale][$key."\0".$placeholder] = [
                'key' => $key,
                'placeholder' => $placeholder,
            ];
        }
    }

    private function recordMissingTranslation(string $key, string $locale): void
    {
        $sentence = ! str_contains($key, '::')
            && preg_match('/\A[^\s.]+(?:\.[^\s.]+)+\z/u', $key) !== 1;

        if (! $this->hasForLocale($key, $locale) && (! $sentence || $locale !== $this->fallback)) {
            $this->missing[$locale][$key] = true;
        }
    }

    /**
     * @template TResult
     *
     * @param Closure(): TResult $callback
     *
     * @return TResult
     */
    private function withoutRecording(Closure $callback): mixed
    {
        $recording = $this->recording;
        $this->recording = false;

        try {
            return $callback();
        } finally {
            $this->recording = $recording;
        }
    }
}
