<?php

namespace OPGG\LaravelMcpServer;

use OPGG\LaravelMcpServer\Providers\SseServiceProvider;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelMcpServerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-mcp-server')
            ->hasConfigFile();
    }

    public function register(): void
    {
        parent::register();
        $this->app->register(SseServiceProvider::class);
    }
}
