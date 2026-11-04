<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Discovery;

use Composer\ClassMapGenerator\ClassMapGenerator;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use ReflectionClass;
use Throwable;

final class MessageDiscovery
{
    /**
     * @param list<string> $paths
     * @param list<class-string> $classNames
     *
     * @return array{classes: list<class-string<Mailable|Notification>>, errors: list<string>}
     */
    public function discover(array $paths, array $classNames = []): array
    {
        $generator = new ClassMapGenerator();
        $classes = [];
        $errors = [];

        foreach ($paths as $path) {
            try {
                $generator->scanPaths($path);
            } catch (Throwable $throwable) {
                $errors[] = $path.': '.$throwable->getMessage();
            }
        }

        foreach (array_unique([
            ...array_keys($generator->getClassMap()->getMap()),
            ...$classNames,
        ]) as $className) {
            try {
                if ($this->isMessage($className)) {
                    $classes[] = $className;
                }
            } catch (Throwable $throwable) {
                $errors[] = $className.': '.$throwable->getMessage();
            }
        }

        sort($classes);

        return ['classes' => $classes, 'errors' => $errors];
    }

    /**
     * @phpstan-assert-if-true class-string<Mailable|Notification> $className
     */
    private function isMessage(string $className): bool
    {
        if (! class_exists($className)) {
            return false;
        }

        $class = new ReflectionClass($className);

        return $class->isInstantiable() && (
            $class->isSubclassOf(Mailable::class)
            || ($class->isSubclassOf(Notification::class)
                && $class->hasMethod('toMail')
                && $class->getMethod('toMail')->isPublic())
        );
    }
}
