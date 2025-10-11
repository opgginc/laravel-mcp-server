<?php

use Illuminate\Support\Arr;
use OPGG\LaravelMcpServer\LaravelMcpServerServiceProvider;
use OPGG\LaravelMcpServer\Server\MCPServer;

beforeEach(function () {
    $this->app->singleton(MCPServer::class, fn () => \Mockery::mock(MCPServer::class));

    $this->app['config']->set('mcp-server.enabled', true);
    $this->app['config']->set('mcp-server.default_path', '/mcp');
    $this->app['config']->set('mcp-server.middlewares', ['auth']);
    $this->app['config']->set('mcp-server.domain', null);
});

function lumenRegisteredRoutes($app, ?string $domain = null): array
{
    $routes = [];

    foreach ($app->router->getRoutes() as $route) {
        $action = $route['action'] ?? [];
        $routeDomain = $action['domain'] ?? null;

        if ($domain !== null && $routeDomain !== null && $routeDomain !== $domain) {
            continue;
        }

        if ($domain === null && $routeDomain !== null) {
            continue;
        }

        $routes[] = [
            'method' => $route['method'],
            'uri' => $route['uri'],
            'middleware' => Arr::wrap($action['middleware'] ?? []),
            'domain' => $routeDomain,
        ];
    }

    usort($routes, fn ($a, $b) => [$a['uri'], $a['method']] <=> [$b['uri'], $b['method']]);

    return $routes;
}

it('registers streamable http routes in lumen', function () {
    $this->app['config']->set('mcp-server.server_provider', 'streamable_http');

    $provider = new LaravelMcpServerServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    $routes = lumenRegisteredRoutes($this->app);

    expect($routes)->sequence(
        fn ($route) => $route
            ->uri->toBe('/mcp')
            ->method->toBe('GET')
            ->middleware->toContain('auth'),
        fn ($route) => $route
            ->uri->toBe('/mcp')
            ->method->toBe('POST')
            ->middleware->toContain('auth'),
    );
});

it('registers sse routes with domain restriction in lumen', function () {
    $this->app['config']->set('mcp-server.server_provider', 'sse');
    $this->app['config']->set('mcp-server.domain', 'lumen.example.com');

    $provider = new LaravelMcpServerServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    $routes = lumenRegisteredRoutes($this->app, 'lumen.example.com');

    expect($routes)->sequence(
        fn ($route) => $route
            ->uri->toBe('/mcp/message')
            ->method->toBe('POST'),
        fn ($route) => $route
            ->uri->toBe('/mcp/sse')
            ->method->toBe('GET'),
    );
});

it('skips registration when lumen server disabled', function () {
    $this->app['config']->set('mcp-server.enabled', false);
    $this->app['config']->set('mcp-server.server_provider', 'streamable_http');

    $provider = new LaravelMcpServerServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    $routes = lumenRegisteredRoutes($this->app);

    expect($routes)->toBeEmpty();
});
