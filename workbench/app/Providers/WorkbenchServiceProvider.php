<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;

final class WorkbenchServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        config([
            'app.name' => 'Studio Goods',
            'mail.from' => ['address' => 'hello@studio.test', 'name' => 'Studio Goods'],
            'message-preview.discovery.paths' => [__DIR__.'/..'],
        ]);

        app()->useLangPath(__DIR__.'/../../lang');
    }
}
