<?php

use Illuminate\Support\Facades\Route;
use OPGG\LaravelMcpServer\LaravelMcpServer;
use OPGG\LaravelMcpServer\LaravelMcpServerServiceProvider;
use OPGG\LaravelMcpServer\Routing\McpEndpointRegistry;
use OPGG\LaravelMcpServer\Routing\McpRouteRegistrar;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\AutoStructuredArrayTool;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\LegacyArrayTool;

function mcpRoutes(string $uri): array
{
    return array_values(array_filter(
        iterator_to_array(Route::getRoutes()),
        fn ($route) => '/'.$route->uri() === $uri || $route->uri() === ltrim($uri, '/')
    ));
}

function bootProvider(): void
{
    app()->register(LaravelMcpServerServiceProvider::class);
    app()->getProvider(LaravelMcpServerServiceProvider::class)->boot();
}

it('registers the Route::mcp macro', function () {
    bootProvider();

    expect(app('router')::hasMacro('mcp'))->toBeTrue();
});

it('registers laravel mcp helper service in the container', function () {
    bootProvider();

    expect(app()->bound(LaravelMcpServer::class))->toBeTrue();
});

it('does not auto-register MCP routes', function () {
    bootProvider();

    expect(mcpRoutes('/mcp'))->toBeEmpty();
});

it('registers GET and POST routes via Route::mcp', function () {
    bootProvider();

    Route::mcp('/mcp');

    $routes = mcpRoutes('/mcp');
    expect($routes)->toHaveCount(2);
    expect(collect($routes)->pluck('methods')->flatten()->contains('POST'))->toBeTrue();
    expect(collect($routes)->pluck('methods')->flatten()->contains('GET'))->toBeTrue();
});

it('stores endpoint definitions from fluent route builder', function () {
    bootProvider();

    Route::mcp('/game-tools')
        ->setServerInfo(
            name: 'Game Tools',
            version: '2.0.0',
            title: 'Game Tools API',
            description: 'Tool endpoint for game operations',
            websiteUrl: 'https://example.com/mcp',
            icons: [
                ['src' => 'https://example.com/icon.png', 'mimeType' => 'image/png', 'sizes' => ['512x512'], 'theme' => 'dark'],
            ],
            instructions: 'Use this endpoint for game related operations.',
        )
        ->tools([LegacyArrayTool::class])
        ->toolListChanged()
        ->resourcesSubscribe()
        ->resourcesListChanged()
        ->promptsListChanged()
        ->toolsPageSize(7);

    /** @var McpEndpointRegistry $registry */
    $registry = app(McpEndpointRegistry::class);
    $definitions = array_values($registry->all());

    expect($definitions)->toHaveCount(1);
    expect($definitions[0]->path)->toBe('/game-tools');
    expect($definitions[0]->name)->toBe('Game Tools');
    expect($definitions[0]->version)->toBe('2.0.0');
    expect($definitions[0]->title)->toBe('Game Tools API');
    expect($definitions[0]->description)->toBe('Tool endpoint for game operations');
    expect($definitions[0]->websiteUrl)->toBe('https://example.com/mcp');
    expect($definitions[0]->icons)->toBe([
        ['src' => 'https://example.com/icon.png', 'mimeType' => 'image/png', 'sizes' => ['512x512'], 'theme' => 'dark'],
    ]);
    expect($definitions[0]->instructions)->toBe('Use this endpoint for game related operations.');
    expect($definitions[0]->tools)->toBe([LegacyArrayTool::class]);
    expect($definitions[0]->toolListChanged)->toBeTrue();
    expect($definitions[0]->resourcesSubscribe)->toBeTrue();
    expect($definitions[0]->resourcesListChanged)->toBeTrue();
    expect($definitions[0]->promptsListChanged)->toBeTrue();
    expect($definitions[0]->toolsPageSize)->toBe(7);
});

it('keeps existing name and version when setServerInfo is partially applied', function () {
    bootProvider();

    Route::mcp('/partial-server-info')
        ->setName('Initial Name')
        ->setVersion('9.9.9')
        ->setServerInfo(
            description: 'Only description is updated',
            instructions: 'Follow these instructions.',
        );

    /** @var McpEndpointRegistry $registry */
    $registry = app(McpEndpointRegistry::class);
    $definitions = array_values($registry->all());

    expect($definitions)->toHaveCount(1);
    expect($definitions[0]->name)->toBe('Initial Name');
    expect($definitions[0]->version)->toBe('9.9.9');
    expect($definitions[0]->description)->toBe('Only description is updated');
    expect($definitions[0]->instructions)->toBe('Follow these instructions.');
});

it('allows setName and setVersion to override setServerInfo values', function () {
    bootProvider();

    Route::mcp('/server-info-override')
        ->setServerInfo(
            name: 'Old Name',
            version: '1.0.0',
        )
        ->setName('New Name')
        ->setVersion('2.0.0');

    /** @var McpEndpointRegistry $registry */
    $registry = app(McpEndpointRegistry::class);
    $definitions = array_values($registry->all());

    expect($definitions)->toHaveCount(1);
    expect($definitions[0]->name)->toBe('New Name');
    expect($definitions[0]->version)->toBe('2.0.0');
});

it('allows setServerInfo to override previously set name and version when provided', function () {
    bootProvider();

    Route::mcp('/server-info-priority')
        ->setName('Initial Name')
        ->setVersion('0.0.1')
        ->setServerInfo(
            name: 'Final Name',
            version: '3.2.1',
        );

    /** @var McpEndpointRegistry $registry */
    $registry = app(McpEndpointRegistry::class);
    $definitions = array_values($registry->all());

    expect($definitions)->toHaveCount(1);
    expect($definitions[0]->name)->toBe('Final Name');
    expect($definitions[0]->version)->toBe('3.2.1');
});

it('supports domain and middleware with standard route groups', function () {
    bootProvider();

    Route::domain('api.example.com')->middleware(['auth:api'])->group(function () {
        Route::mcp('/secure-mcp');
    });

    $routes = mcpRoutes('/secure-mcp');
    expect($routes)->toHaveCount(2);

    foreach ($routes as $route) {
        expect($route->getDomain())->toBe('api.example.com');
        expect($route->middleware())->toContain('auth:api');
        expect($route->getAction(McpRouteRegistrar::ROUTE_DEFAULT_ENDPOINT_KEY))->toBeString();
    }
});

it('registers multiple endpoints independently', function () {
    bootProvider();

    Route::mcp('/first')->setName('First')->setVersion('1.0.0');
    Route::mcp('/second')->setName('Second')->setVersion('2.0.0');

    /** @var McpEndpointRegistry $registry */
    $registry = app(McpEndpointRegistry::class);
    $definitions = array_values($registry->all());

    expect($definitions)->toHaveCount(2);
    expect(array_column($definitions, 'path'))->toContain('/first', '/second');
});

it('replaces existing endpoint definition when same path and domain are registered again', function () {
    bootProvider();

    Route::mcp('/mcp')->tools([LegacyArrayTool::class]);
    Route::mcp('/mcp')->tools([AutoStructuredArrayTool::class]);

    $routes = mcpRoutes('/mcp');
    expect($routes)->toHaveCount(2);

    $routeEndpointIds = array_values(array_unique(array_map(
        fn ($route) => $route->getAction(McpRouteRegistrar::ROUTE_DEFAULT_ENDPOINT_KEY),
        $routes
    )));

    expect($routeEndpointIds)->toHaveCount(1);

    /** @var McpEndpointRegistry $registry */
    $registry = app(McpEndpointRegistry::class);
    $definitions = array_values($registry->all());

    expect($definitions)->toHaveCount(1);
    expect($definitions[0]->id)->toBe($routeEndpointIds[0]);
    expect($definitions[0]->tools)->toBe([AutoStructuredArrayTool::class]);
});

it('can register endpoint via laravel mcp helper service', function () {
    bootProvider();

    app(LaravelMcpServer::class)
        ->mcp('/helper-mcp')
        ->tools([LegacyArrayTool::class]);

    $routes = mcpRoutes('/helper-mcp');

    expect($routes)->toHaveCount(2);
});
