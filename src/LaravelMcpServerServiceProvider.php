<?php

namespace OPGG\LaravelMcpServer;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use OPGG\LaravelMcpServer\Console\Commands\MakeMcpToolCommand;
use OPGG\LaravelMcpServer\Console\Commands\TestMcpToolCommand;
use OPGG\LaravelMcpServer\Http\Controllers\MessageController;
use OPGG\LaravelMcpServer\Http\Controllers\SseController;
use OPGG\LaravelMcpServer\Providers\SseServiceProvider;
use OPGG\LaravelMcpServer\Server\MCPServer;
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
            ->hasConfigFile()
            ->hasCommands([
                MakeMcpToolCommand::class,
                TestMcpToolCommand::class,
            ]);
    }

    public function register(): void
    {
        parent::register();
        $this->app->register(SseServiceProvider::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->registerRoutes();
    }

    /**
     * Register the routes for the MCP Server
     */
    protected function registerRoutes(): void
    {
        // Skip route registration if the server is disabled
        if (! Config::get('mcp-server.enabled', true)) {
            return;
        }

        // Skip route registration if MCPServer instance doesn't exist
        if (! app()->has(MCPServer::class)) {
            return;
        }

        $path = Config::get('mcp-server.default_path');
        $middlewares = Config::get('mcp-server.middlewares', []);

        Route::get("{$path}/sse", [SseController::class, 'handle'])
            ->middleware($middlewares);

        Route::post("{$path}/message", [MessageController::class, 'handle']);
    }
}
