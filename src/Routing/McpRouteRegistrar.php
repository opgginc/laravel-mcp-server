<?php

namespace OPGG\LaravelMcpServer\Routing;

use Illuminate\Routing\Router as LaravelRouter;
use OPGG\LaravelMcpServer\Http\Controllers\StreamableHttpController;
use OPGG\LaravelMcpServer\Server\McpServerFactory;

final class McpRouteRegistrar
{
    public const ROUTE_DEFAULT_ENDPOINT_KEY = 'mcp_endpoint_id';

    public const ROUTE_ENDPOINT_DEFINITION_KEY = 'mcp_endpoint_definition';

    private const LUMEN_ROUTER_CLASS = 'Laravel\\Lumen\\Routing\\Router';

    public function __construct(private readonly McpEndpointRegistry $registry) {}

    public function register(object $router, string $path = '/'): McpRouteBuilder
    {
        $lumenRouterClass = self::LUMEN_ROUTER_CLASS;
        $isLumenRouter = class_exists($lumenRouterClass) && $router instanceof $lumenRouterClass;

        return match (true) {
            $router instanceof LaravelRouter => $this->registerLaravel($router, $path),
            $isLumenRouter => $this->registerLumen($router, $path),
            default => throw new \InvalidArgumentException('Unsupported router instance for MCP route registration.'),
        };
    }

    public function registerLaravel(LaravelRouter $router, string $path = '/'): McpRouteBuilder
    {
        $normalizedPath = McpEndpointDefinition::normalizePath($path);
        $uri = $this->toRouteUri($normalizedPath);

        $this->cleanupExistingLaravelEndpoint($router, $uri, $this->resolveLaravelGroupDomain($router));

        $definition = $this->registry->create($normalizedPath);

        $router->get($uri, [
            'uses' => StreamableHttpController::class.'@getHandle',
            self::ROUTE_DEFAULT_ENDPOINT_KEY => $definition->id,
            self::ROUTE_ENDPOINT_DEFINITION_KEY => $definition->toArray(),
        ]);
        $router->post($uri, [
            'uses' => StreamableHttpController::class.'@postHandle',
            self::ROUTE_DEFAULT_ENDPOINT_KEY => $definition->id,
            self::ROUTE_ENDPOINT_DEFINITION_KEY => $definition->toArray(),
        ]);

        return new McpRouteBuilder($this->registry, $definition->id);
    }

    public function registerLumen(object $router, string $path = '/'): McpRouteBuilder
    {
        $lumenRouterClass = self::LUMEN_ROUTER_CLASS;
        if (! class_exists($lumenRouterClass) || ! $router instanceof $lumenRouterClass) {
            throw new \InvalidArgumentException('Unsupported Lumen router instance for MCP route registration.');
        }

        $normalizedPath = McpEndpointDefinition::normalizePath($path);
        $uri = $this->toRouteUri($normalizedPath);

        $this->cleanupExistingLumenEndpoint($router, $uri, $this->resolveLumenGroupDomain($router));

        $definition = $this->registry->create($normalizedPath);

        $router->get($uri, [
            'uses' => StreamableHttpController::class.'@getHandle',
            self::ROUTE_DEFAULT_ENDPOINT_KEY => $definition->id,
            self::ROUTE_ENDPOINT_DEFINITION_KEY => $definition->toArray(),
        ]);
        $router->post($uri, [
            'uses' => StreamableHttpController::class.'@postHandle',
            self::ROUTE_DEFAULT_ENDPOINT_KEY => $definition->id,
            self::ROUTE_ENDPOINT_DEFINITION_KEY => $definition->toArray(),
        ]);

        return new McpRouteBuilder($this->registry, $definition->id);
    }

    public function syncLaravelRouteEndpointDefinition(McpEndpointDefinition $definition): void
    {
        if (! app()->bound('router')) {
            return;
        }

        /** @var mixed $router */
        $router = app('router');
        if (! $router instanceof LaravelRouter) {
            return;
        }

        foreach ($router->getRoutes()->getRoutes() as $route) {
            $endpointId = $route->getAction(self::ROUTE_DEFAULT_ENDPOINT_KEY);
            if (! is_string($endpointId) || $endpointId !== $definition->id) {
                continue;
            }

            $action = $route->getAction();
            $action[self::ROUTE_ENDPOINT_DEFINITION_KEY] = $definition->toArray();
            $route->setAction($action);
        }
    }

    private function toRouteUri(string $normalizedPath): string
    {
        if ($normalizedPath === '/') {
            return '/';
        }

        return ltrim($normalizedPath, '/');
    }

    private function normalizeRouteUriForMatching(string $uri): string
    {
        return $this->toRouteUri(McpEndpointDefinition::normalizePath($uri));
    }

    private function cleanupExistingLaravelEndpoint(LaravelRouter $router, string $uri, ?string $domain): void
    {
        foreach ($router->getRoutes()->getRoutes() as $route) {
            if ($route->uri() !== $uri) {
                continue;
            }

            if ($this->normalizeDomain($route->getDomain()) !== $this->normalizeDomain($domain)) {
                continue;
            }

            $endpointId = $route->getAction(self::ROUTE_DEFAULT_ENDPOINT_KEY);
            if (is_string($endpointId) && $endpointId !== '') {
                $this->registry->remove($endpointId);
                $this->clearFactoryEndpointCache($endpointId);
            }
        }
    }

    private function cleanupExistingLumenEndpoint(object $router, string $uri, ?string $domain): void
    {
        if (! method_exists($router, 'getRoutes')) {
            return;
        }

        $routes = $router->getRoutes();
        if (! is_iterable($routes)) {
            return;
        }

        $normalizedTargetUri = $this->normalizeRouteUriForMatching($uri);

        foreach ($routes as $route) {
            if (! is_array($route)) {
                continue;
            }

            $routeUri = $route['uri'] ?? null;
            if (! is_string($routeUri)) {
                continue;
            }

            if ($this->normalizeRouteUriForMatching($routeUri) !== $normalizedTargetUri) {
                continue;
            }

            $action = $route['action'] ?? null;
            if (! is_array($action)) {
                continue;
            }

            $routeDomain = $action['domain'] ?? null;
            if ($this->normalizeDomain(is_string($routeDomain) ? $routeDomain : null) !== $this->normalizeDomain($domain)) {
                continue;
            }

            $endpointId = $action[self::ROUTE_DEFAULT_ENDPOINT_KEY] ?? null;
            if (is_string($endpointId) && $endpointId !== '') {
                $this->registry->remove($endpointId);
                $this->clearFactoryEndpointCache($endpointId);
            }
        }
    }

    private function resolveLaravelGroupDomain(LaravelRouter $router): ?string
    {
        if (! $router->hasGroupStack()) {
            return null;
        }

        $groupStack = $router->getGroupStack();
        if ($groupStack === []) {
            return null;
        }

        $lastGroup = $groupStack[array_key_last($groupStack)];
        $domain = is_array($lastGroup) ? ($lastGroup['domain'] ?? null) : null;

        return is_string($domain) && $domain !== '' ? $domain : null;
    }

    private function resolveLumenGroupDomain(object $router): ?string
    {
        if (! method_exists($router, 'getGroupStack')) {
            return null;
        }

        $groupStack = $router->getGroupStack();
        if (! is_array($groupStack) || $groupStack === []) {
            return null;
        }

        $lastGroup = $groupStack[array_key_last($groupStack)];
        $domain = is_array($lastGroup) ? ($lastGroup['domain'] ?? null) : null;

        return is_string($domain) && $domain !== '' ? $domain : null;
    }

    private function normalizeDomain(?string $domain): ?string
    {
        return is_string($domain) && $domain !== '' ? $domain : null;
    }

    private function clearFactoryEndpointCache(string $endpointId): void
    {
        if (! app()->bound(McpServerFactory::class)) {
            return;
        }

        app(McpServerFactory::class)->clearEndpointCache($endpointId);
    }
}
