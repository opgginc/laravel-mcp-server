<?php

use Illuminate\Support\Arr;
use OPGG\LaravelMcpServer\LaravelMcpServerServiceProvider;
use OPGG\LaravelMcpServer\Routing\McpEndpointRegistry;
use OPGG\LaravelMcpServer\Routing\McpRoute;
use OPGG\LaravelMcpServer\Routing\McpRouteRegistrar;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\AutoStructuredArrayTool;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\LegacyArrayTool;

function lumenRegisteredRoutes($app, ?string $uri = null): array
{
    $routes = [];

    foreach ($app->router->getRoutes() as $route) {
        $action = $route['action'] ?? [];
        $routeUri = $route['uri'] ?? '';

        if ($uri !== null && $routeUri !== $uri) {
            continue;
        }

        $routes[] = [
            'method' => $route['method'],
            'uri' => $routeUri,
            'middleware' => Arr::wrap($action['middleware'] ?? []),
            'endpoint_id' => $action[McpRouteRegistrar::ROUTE_DEFAULT_ENDPOINT_KEY] ?? null,
        ];
    }

    usort($routes, fn ($a, $b) => [$a['uri'], $a['method']] <=> [$b['uri'], $b['method']]);

    return $routes;
}

it('does not auto-register routes in lumen', function () {
    $provider = new LaravelMcpServerServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    expect(lumenRegisteredRoutes($this->app))->toBeEmpty();
});

it('registers streamable routes through McpRouteRegistrar in lumen', function () {
    $provider = new LaravelMcpServerServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    app(McpRouteRegistrar::class)
        ->registerLumen($this->app->router, '/mcp')
        ->tools([LegacyArrayTool::class]);

    $routes = lumenRegisteredRoutes($this->app, '/mcp');

    expect($routes)->sequence(
        fn ($route) => $route
            ->uri->toBe('/mcp')
            ->method->toBe('GET')
            ->endpoint_id->toBeString(),
        fn ($route) => $route
            ->uri->toBe('/mcp')
            ->method->toBe('POST')
            ->endpoint_id->toBeString(),
    );
});

it('supports registering route via McpRoute helper in lumen', function () {
    $provider = new LaravelMcpServerServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    McpRoute::register('/lumen-mcp')->setServerInfo(name: 'Lumen MCP');

    $routes = lumenRegisteredRoutes($this->app, '/lumen-mcp');

    expect($routes)->toHaveCount(2);
});

it('replaces existing lumen endpoint definition when same path is registered again', function () {
    $provider = new LaravelMcpServerServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    $registrar = app(McpRouteRegistrar::class);

    $firstBuilder = $registrar->registerLumen($this->app->router, '/mcp');
    $firstBuilder->tools([LegacyArrayTool::class]);
    $firstEndpointId = $firstBuilder->endpointId();

    $secondBuilder = $registrar->registerLumen($this->app->router, '/mcp');
    $secondBuilder->tools([AutoStructuredArrayTool::class]);
    $secondEndpointId = $secondBuilder->endpointId();

    /** @var McpEndpointRegistry $registry */
    $registry = app(McpEndpointRegistry::class);

    expect($secondEndpointId)->not->toBe($firstEndpointId);
    expect($registry->find($firstEndpointId))->toBeNull();
    expect($registry->find($secondEndpointId)?->tools)->toBe([AutoStructuredArrayTool::class]);
});

it('keeps route middleware from lumen groups', function () {
    $provider = new LaravelMcpServerServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    $this->app->router->group(['middleware' => ['auth']], function ($router) {
        app(McpRouteRegistrar::class)->registerLumen($router, '/secure-mcp');
    });

    $routes = lumenRegisteredRoutes($this->app, '/secure-mcp');
    expect($routes)->toHaveCount(2);
    expect($routes[0]['middleware'])->toContain('auth');
    expect($routes[1]['middleware'])->toContain('auth');
});
