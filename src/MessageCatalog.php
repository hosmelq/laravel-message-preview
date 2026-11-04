<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview;

use Closure;
use HosmelQ\MessagePreview\Discovery\MessageDiscovery;
use HosmelQ\MessagePreview\Support\PreviewConfig;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use LogicException;
use Throwable;

final class MessageCatalog
{
    /**
     * @var array<class-string<Mailable|Notification>, Closure(): PreviewConfig>
     */
    private array $registered = [];

    public function __construct(private readonly MessageDiscovery $discovery) {}

    /**
     * @return array{errors: list<string>, messages: list<ConfiguredMessage>}
     */
    public function all(): array
    {
        $discovered = $this->discovery->discover(
            config('message-preview.discovery.paths'),
            array_keys($this->registered),
        );

        return [
            'errors' => $discovered['errors'],
            'messages' => array_map($this->configure(...), $discovered['classes']),
        ];
    }

    public function find(string $key, string $variant): null|ConfiguredMessage
    {
        return array_find(
            $this->all()['messages'],
            static fn (ConfiguredMessage $message): bool => $message->key === $key
                && in_array($variant, $message->config->getVariantNames(), true),
        );
    }

    /**
     * @param class-string<Mailable|Notification> $className
     * @param Closure(): PreviewConfig $config
     */
    public function register(string $className, Closure $config): void
    {
        $this->registered[$className] = $config;
    }

    /**
     * @param class-string<Mailable|Notification> $className
     */
    private function config(string $className): PreviewConfig
    {
        if (isset($this->registered[$className])) {
            return $this->registered[$className]();
        }

        $config = method_exists($className, 'previewConfig')
            ? $className::previewConfig()
            : PreviewConfig::configure();

        return $config instanceof PreviewConfig ? $config : throw new LogicException(
            $className.'::previewConfig() must return a '.PreviewConfig::class.'.',
        );
    }

    /**
     * @param class-string<Mailable|Notification> $className
     */
    private function configure(string $className): ConfiguredMessage
    {
        try {
            return new ConfiguredMessage($className, $this->config($className));
        } catch (Throwable $throwable) {
            return new ConfiguredMessage($className, PreviewConfig::configure(), $throwable);
        }
    }
}
