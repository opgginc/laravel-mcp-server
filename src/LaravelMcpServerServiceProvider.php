<?php

namespace OPGG\LaravelMcpServer;

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

        $this->registerConfiguration();

        $provider = match ($this->getConfig('mcp-server.server_provider')) {
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
        if (! $this->getConfig('mcp-server.enabled', true)) {
            return;
        }

        // Skip route registration if MCPServer instance doesn't exist
        if (! app()->has(MCPServer::class)) {
            return;
        }

        $path = $this->getConfig('mcp-server.default_path');
        $middlewares = $this->getConfig('mcp-server.middlewares', []);
        $domain = $this->getConfig('mcp-server.domain');
        $provider = $this->getConfig('mcp-server.server_provider');

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
        $router = $this->app->make('router');

        if ($this->isLumenRouter($router)) {
            $this->registerLumenRoutes($router, $domain, $path, $middlewares, $provider);

            return;
        }

        // Build route configuration
        $routeRegistrar = Route::middleware($middlewares);

        // Apply domain restriction if specified
        if ($domain !== null) {
            $routeRegistrar = $routeRegistrar->domain($domain);
        }

        // Register provider-specific routes
        switch ($provider) {
            case 'sse':
                $routeRegistrar->get("{$path}/sse", [SseController::class, 'handle']);
                $routeRegistrar->post("{$path}/message", [MessageController::class, 'handle']);
                break;

            case 'streamable_http':
                $routeRegistrar->get($path, [StreamableHttpController::class, 'getHandle']);
                $routeRegistrar->post($path, [StreamableHttpController::class, 'postHandle']);
                break;
        }
    }

    protected function registerConfiguration(): void
    {
        if ($this->isLumenApplication() && ! $this->app['config']->has('mcp-server')) {
            $this->app->configure('mcp-server');
        }

        $this->mergeConfigFrom(__DIR__.'/../config/mcp-server.php', 'mcp-server');
    }

    protected function getConfig(string $key, $default = null)
    {
        if ($this->app->bound('config')) {
            return $this->app['config']->get($key, $default);
        }

        return $default;
    }

    protected function isLumenApplication(): bool
    {
        return class_exists(\Laravel\Lumen\Application::class) && $this->app instanceof \Laravel\Lumen\Application;
    }

    protected function isLumenRouter($router): bool
    {
        return class_exists(\Laravel\Lumen\Routing\Router::class) && $router instanceof \Laravel\Lumen\Routing\Router;
    }

    protected function registerLumenRoutes($router, ?string $domain, string $path, array $middlewares, string $provider): void
    {
        $groupAttributes = [];

        if (! empty($middlewares)) {
            $groupAttributes['middleware'] = $middlewares;
        }

        if ($domain !== null) {
            $groupAttributes['domain'] = $domain;
        }

        $router->group($groupAttributes, function ($router) use ($path, $provider) {
            switch ($provider) {
                case 'sse':
                    $router->get("{$path}/sse", [SseController::class, 'handle']);
                    $router->post("{$path}/message", [MessageController::class, 'handle']);
                    break;

                case 'streamable_http':
                default:
                    $router->get($path, [StreamableHttpController::class, 'getHandle']);
                    $router->post($path, [StreamableHttpController::class, 'postHandle']);
                    break;
            }
        });
    }
}
