<?php

namespace OPGG\LaravelMcpServer;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use OPGG\LaravelMcpServer\Console\Commands\MakeMcpNotificationCommand;
use OPGG\LaravelMcpServer\Console\Commands\MakeMcpPromptCommand;
use OPGG\LaravelMcpServer\Console\Commands\MakeMcpResourceCommand;
use OPGG\LaravelMcpServer\Console\Commands\MakeMcpResourceTemplateCommand;
use OPGG\LaravelMcpServer\Console\Commands\MakeMcpToolCommand;
use OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand;
use OPGG\LaravelMcpServer\Console\Commands\MigrateToolsCommand;
use OPGG\LaravelMcpServer\Console\Commands\TestMcpToolCommand;
use OPGG\LaravelMcpServer\Http\Controllers\MessageController;
use OPGG\LaravelMcpServer\Http\Controllers\SseController;
use OPGG\LaravelMcpServer\Http\Controllers\StreamableHttpController;
use OPGG\LaravelMcpServer\Providers\SseServiceProvider;
use OPGG\LaravelMcpServer\Providers\StreamableHttpServiceProvider;
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
            ->hasConfigFile('mcp-server')
            ->hasCommands([
                MakeMcpToolCommand::class,
                MakeMcpResourceCommand::class,
                MakeMcpResourceTemplateCommand::class,
                MakeMcpPromptCommand::class,
                MakeMcpNotificationCommand::class,
                MakeSwaggerMcpToolCommand::class,
                TestMcpToolCommand::class,
                MigrateToolsCommand::class,
            ]);
    }

    public function register(): void
    {
        parent::register();

        $provider = match (Config::get('mcp-server.server_provider')) {
            'streamable_http' => StreamableHttpServiceProvider::class,
            default => SseServiceProvider::class,
        };

        $this->app->register($provider);
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
        $domain = Config::get('mcp-server.domain');
        $provider = Config::get('mcp-server.server_provider');

        // Handle multiple domains support
        $domains = $this->normalizeDomains($domain);

        // Register routes for each domain
        foreach ($domains as $domainName) {
            $this->registerRoutesForDomain($domainName, $path, $middlewares, $provider);
        }
    }

    /**
     * Normalize domain configuration to array format
     *
     * @param  null|string|array  $domain
     */
    protected function normalizeDomains($domain): array
    {
        if ($domain === null) {
            return [null]; // No domain restriction
        }

        if (is_string($domain)) {
            return [$domain]; // Single domain
        }

        if (is_array($domain)) {
            return $domain; // Multiple domains
        }

        // Invalid configuration, default to no restriction
        return [null];
    }

    /**
     * Register routes for a specific domain
     */
    protected function registerRoutesForDomain(?string $domain, string $path, array $middlewares, string $provider): void
    {
        // Build route configuration
        $router = Route::middleware($middlewares);

        // Apply domain restriction if specified
        if ($domain !== null) {
            $router = $router->domain($domain);
        }

        // Register provider-specific routes
        switch ($provider) {
            case 'sse':
                $router->get("{$path}/sse", [SseController::class, 'handle']);
                $router->post("{$path}/message", [MessageController::class, 'handle']);
                break;

            case 'streamable_http':
                $router->get($path, [StreamableHttpController::class, 'getHandle']);
                $router->post($path, [StreamableHttpController::class, 'postHandle']);
                break;
        }
    }
}
