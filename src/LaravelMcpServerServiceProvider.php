<?php

namespace OPGG\LaravelMcpServer;

use Illuminate\Routing\Router as LaravelRouter;
use OPGG\LaravelMcpServer\Console\Commands\ExportToolsOpenApiCommand;
use OPGG\LaravelMcpServer\Console\Commands\MakeMcpNotificationCommand;
use OPGG\LaravelMcpServer\Console\Commands\MakeMcpPromptCommand;
use OPGG\LaravelMcpServer\Console\Commands\MakeMcpResourceCommand;
use OPGG\LaravelMcpServer\Console\Commands\MakeMcpResourceTemplateCommand;
use OPGG\LaravelMcpServer\Console\Commands\MakeMcpToolCommand;
use OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand;
use OPGG\LaravelMcpServer\Console\Commands\MigrateToolsCommand;
use OPGG\LaravelMcpServer\Console\Commands\TestMcpToolCommand;
use OPGG\LaravelMcpServer\Routing\McpEndpointRegistry;
use OPGG\LaravelMcpServer\Routing\McpRouteRegistrar;
use OPGG\LaravelMcpServer\Server\McpServerFactory;
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
            ->hasCommands([
                MakeMcpToolCommand::class,
                MakeMcpResourceCommand::class,
                MakeMcpResourceTemplateCommand::class,
                MakeMcpPromptCommand::class,
                MakeMcpNotificationCommand::class,
                MakeSwaggerMcpToolCommand::class,
                ExportToolsOpenApiCommand::class,
                TestMcpToolCommand::class,
                MigrateToolsCommand::class,
            ]);
    }

    public function register(): void
    {
        parent::register();

        $this->app->singleton(LaravelMcpServer::class);
        $this->app->singleton(McpEndpointRegistry::class);
        $this->app->singleton(McpRouteRegistrar::class);
        $this->app->singleton(McpServerFactory::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->registerRouteMacros();
    }

    protected function registerRouteMacros(): void
    {
        if (! class_exists(LaravelRouter::class)) {
            return;
        }

        if (LaravelRouter::hasMacro('mcp')) {
            return;
        }

        LaravelRouter::macro('mcp', function (string $path = '/') {
            return app(McpRouteRegistrar::class)->register($this, $path);
        });
    }
}
