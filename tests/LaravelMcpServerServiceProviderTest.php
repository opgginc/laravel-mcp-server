<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use OPGG\LaravelMcpServer\LaravelMcpServerServiceProvider;
use OPGG\LaravelMcpServer\Server\MCPServer;

beforeEach(function () {
    // Mock MCPServer
    app()->singleton(MCPServer::class, function () {
        return Mockery::mock(MCPServer::class);
    });
});

function getRoutesByDomain(?string $domain = null): array
{
    $routes = [];
    foreach (Route::getRoutes() as $route) {
        $routeDomain = $route->getDomain();
        if ($domain === null && $routeDomain === null) {
            $routes[] = $route;
        } elseif ($domain !== null && $routeDomain === $domain) {
            $routes[] = $route;
        }
    }

    return $routes;
}

function registerProvider(): void
{
    app()->register(LaravelMcpServerServiceProvider::class);
    app()->getProvider(LaravelMcpServerServiceProvider::class)->boot();
}

it('registers routes without domain restriction', function () {
    Config::set('mcp-server.enabled', true);
    Config::set('mcp-server.domain', null);
    Config::set('mcp-server.default_path', '/mcp');
    Config::set('mcp-server.server_provider', 'streamable_http');

    registerProvider();

    $routes = getRoutesByDomain();
    $mcpRoutes = array_filter($routes, fn ($route) => str_contains($route->uri(), 'mcp'));

    expect($mcpRoutes)->toHaveCount(2); // GET and POST routes
});

it('registers routes with single domain restriction', function () {
    Config::set('mcp-server.enabled', true);
    Config::set('mcp-server.domain', 'api.example.com');
    Config::set('mcp-server.default_path', '/mcp');
    Config::set('mcp-server.server_provider', 'streamable_http');

    registerProvider();

    $routes = getRoutesByDomain('api.example.com');
    $mcpRoutes = array_filter($routes, fn ($route) => str_contains($route->uri(), 'mcp'));

    expect($mcpRoutes)->toHaveCount(2);

    foreach ($mcpRoutes as $route) {
        expect($route->getDomain())->toBe('api.example.com');
    }
});

it('registers routes with multiple domain restrictions', function () {
    Config::set('mcp-server.enabled', true);
    Config::set('mcp-server.domain', ['api.example.com', 'admin.example.com', 'app.example.com']);
    Config::set('mcp-server.default_path', '/mcp');
    Config::set('mcp-server.server_provider', 'streamable_http');

    registerProvider();

    $domains = ['api.example.com', 'admin.example.com', 'app.example.com'];

    foreach ($domains as $domain) {
        $routes = getRoutesByDomain($domain);
        $mcpRoutes = array_filter($routes, fn ($route) => str_contains($route->uri(), 'mcp'));

        expect($mcpRoutes)->toHaveCount(2, "Expected 2 routes for domain {$domain}");

        foreach ($mcpRoutes as $route) {
            expect($route->getDomain())->toBe($domain);
        }
    }
});

it('registers routes for sse provider with domain', function () {
    Config::set('mcp-server.enabled', true);
    Config::set('mcp-server.domain', 'sse.example.com');
    Config::set('mcp-server.default_path', '/mcp');
    Config::set('mcp-server.server_provider', 'sse');

    registerProvider();

    $routes = getRoutesByDomain('sse.example.com');
    $mcpRoutes = array_filter($routes, fn ($route) => str_contains($route->uri(), 'mcp'));

    expect($mcpRoutes)->toHaveCount(2); // /mcp/sse and /mcp/message

    $uris = array_map(fn ($route) => $route->uri(), $mcpRoutes);
    expect($uris)->toContain('mcp/sse');
    expect($uris)->toContain('mcp/message');
});

it('does not register routes when server is disabled', function () {
    Config::set('mcp-server.enabled', false);
    Config::set('mcp-server.domain', 'api.example.com');
    Config::set('mcp-server.default_path', '/mcp');
    Config::set('mcp-server.server_provider', 'streamable_http');

    // Count routes before registration
    $beforeCount = count(array_filter(
        iterator_to_array(Route::getRoutes()),
        fn ($route) => str_contains($route->uri(), 'mcp')
    ));

    registerProvider();

    // Count routes after registration
    $afterCount = count(array_filter(
        iterator_to_array(Route::getRoutes()),
        fn ($route) => str_contains($route->uri(), 'mcp')
    ));

    // No new MCP routes should be added when server is disabled
    expect($afterCount)->toBe($beforeCount);
});

it('defaults to no restriction with invalid domain configuration', function () {
    Config::set('mcp-server.enabled', true);
    Config::set('mcp-server.domain', 123); // Invalid type
    Config::set('mcp-server.default_path', '/mcp');
    Config::set('mcp-server.server_provider', 'streamable_http');

    registerProvider();

    // Should default to no domain restriction
    $routes = getRoutesByDomain();
    $mcpRoutes = array_filter($routes, fn ($route) => str_contains($route->uri(), 'mcp'));

    expect($mcpRoutes)->toHaveCount(2);
});

it('applies middlewares to routes', function () {
    Config::set('mcp-server.enabled', true);
    Config::set('mcp-server.domain', null);
    Config::set('mcp-server.default_path', '/mcp');
    Config::set('mcp-server.middlewares', ['auth:api', 'throttle:60,1']);
    Config::set('mcp-server.server_provider', 'streamable_http');

    registerProvider();

    $routes = Route::getRoutes();
    $mcpRoutes = array_filter(iterator_to_array($routes), fn ($route) => str_contains($route->uri(), 'mcp'));

    foreach ($mcpRoutes as $route) {
        $middlewares = $route->middleware();
        expect($middlewares)->toContain('auth:api');
        expect($middlewares)->toContain('throttle:60,1');
    }
});

it('detects Laravel application correctly', function () {
    $provider = new LaravelMcpServerServiceProvider(app());
    
    // Use reflection to test the protected method
    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('isLumen');
    $method->setAccessible(true);
    
    // In a Laravel test environment, this should return false
    expect($method->invoke($provider))->toBeFalse();
});

it('handles routing when Lumen is not detected', function () {
    Config::set('mcp-server.enabled', true);
    Config::set('mcp-server.domain', null);
    Config::set('mcp-server.default_path', '/mcp');
    Config::set('mcp-server.middlewares', ['test-middleware']);
    Config::set('mcp-server.server_provider', 'streamable_http');

    registerProvider();

    $routes = getRoutesByDomain();
    $mcpRoutes = array_filter($routes, fn ($route) => str_contains($route->uri(), 'mcp'));

    expect($mcpRoutes)->toHaveCount(2); // GET and POST routes
    
    // Verify that middleware is applied
    foreach ($mcpRoutes as $route) {
        $middlewares = $route->middleware();
        expect($middlewares)->toContain('test-middleware');
    }
});
