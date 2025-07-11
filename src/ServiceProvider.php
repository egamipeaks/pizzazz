<?php

namespace EgamiPeaks\Pizzazz;

use EgamiPeaks\Pizzazz\Commands\PizzazzCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('pizzazz')
            ->hasConfigFile()
            ->hasViews()
            ->hasCommand(PizzazzCommand::class);
    }

    public function packageRegistered()
    {
        $this->app->singleton(Services\PageCacheKeyService::class);
        $this->app->singleton(Services\PageCacheLogger::class);
        $this->app->singleton(Services\PageCacheFlusher::class);
        $this->app->singleton(Pizzazz::class);
    }
}
