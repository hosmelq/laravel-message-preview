<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview\Tests;

use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Override;

abstract class TestCase extends OrchestraTestCase
{
    use WithWorkbench;

    #[Override]
    protected function defineEnvironment($app): void
    {
        $app->detectEnvironment(fn (): string => 'local');

        $app['config']->set('app.debug', true);
    }
}
