<?php

declare(strict_types=1);

namespace HosmelQ\MessagePreview;

use HosmelQ\CanIEmail\CanIEmail;
use Override;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class MessagePreviewServiceProvider extends PackageServiceProvider
{
    #[Override]
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-message-preview')
            ->hasAssets()
            ->hasConfigFile()
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->publishConfigFile()
                    ->publishAssets()
                    ->askToStarRepoOnGitHub('hosmelq/laravel-message-preview');
            })
            ->hasRoute('web')
            ->hasViews();
    }

    #[Override]
    public function packageRegistered(): void
    {
        $this->app->singleton(CanIEmail::class, CanIEmail::create(...));
        $this->app->singleton(MessageCatalog::class);
    }
}
