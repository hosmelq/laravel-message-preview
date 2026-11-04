<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Support;

use Closure;

final class PreviewConfig
{
    private null|Closure $notifiable = null;

    /**
     * @var array<string, array{message: null|Closure(): object, notifiable: null|Closure}>
     */
    private array $variants = ['default' => ['message' => null, 'notifiable' => null]];

    public static function configure(): self
    {
        return new self();
    }

    /**
     * @return null|Closure(): object
     */
    public function getMessage(string $variant): null|Closure
    {
        return $this->variants[$variant]['message'];
    }

    public function getNotifiable(string $variant): null|Closure
    {
        return $this->variants[$variant]['notifiable'] ?? $this->notifiable;
    }

    /**
     * @return list<string>
     */
    public function getVariantNames(): array
    {
        return array_map(strval(...), array_keys($this->variants));
    }

    /**
     * @param Closure(): object $message
     */
    public function withMessage(Closure $message): self
    {
        $this->variants['default']['message'] = $message;

        return $this;
    }

    /**
     * @template TMessage of object
     *
     * @param Closure(TMessage): object $notifiable
     */
    public function withNotifiable(Closure $notifiable): self
    {
        $this->notifiable = $notifiable;

        return $this;
    }

    /**
     * @template TMessage of object
     *
     * @param Closure(): TMessage $message
     * @param null|Closure(TMessage): object $notifiable
     */
    public function withVariant(
        string $name,
        Closure $message,
        null|Closure $notifiable = null,
    ): self {
        $this->variants[$name] = ['message' => $message, 'notifiable' => $notifiable];

        return $this;
    }
}
