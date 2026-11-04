<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview;

use Illuminate\Foundation\Vite as LaravelVite;

final class Vite extends LaravelVite
{
    public function __construct()
    {
        $this->useBuildDirectory('vendor/message-preview')->useHotFile(__DIR__.'/../resources/hot');
    }
}
