<?php

namespace OPGG\LaravelMcpServer\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use OPGG\LaravelMcpServer\Protocol\MCPProtocol;
use OPGG\LaravelMcpServer\Server\MCPServer;
use OPGG\LaravelMcpServer\Server\ServerCapabilities;
use OPGG\LaravelMcpServer\Services\SseAdapterFactory;
use OPGG\LaravelMcpServer\Services\ToolService\ToolRepository;
use OPGG\LaravelMcpServer\Transports\SseTransport;

/**
 * Server-Sent Events Service Provider
 *
 * Registers the MCPServer as a singleton when server_provider config is set to "sse"
 */
final class SseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (Config::get('mcp-server.server_provider') === 'sse') {
            $this->app->bind(ToolRepository::class, function ($app) {
                $toolRepository = new ToolRepository($app);

                $tools = Config::get('mcp-server.tools', []);
                $toolRepository->registerMany($tools);

                return $toolRepository;
            });

            $this->app->bind(\OPGG\LaravelMcpServer\Services\ResourceService\ResourceRepository::class, function ($app) {
                $resourceRepository = new \OPGG\LaravelMcpServer\Services\ResourceService\ResourceRepository;

                $resources = Config::get('mcp-server.resources', []);
                $resourceRepository->registerMany($resources);

                return $resourceRepository;
            });

            $this->app->singleton(MCPServer::class, function ($app) {
                $transport = new SseTransport;

                $adapterType = Config::get('mcp-server.sse_adapter', 'redis');
                $adapterFactory = new SseAdapterFactory(adapterType: $adapterType);
                $adapter = $adapterFactory->createAdapter();

                $transport->setAdapter($adapter);

                $protocol = new MCPProtocol($transport);

                $serverInfo = Config::get('mcp-server.server');

                $capabilities = new ServerCapabilities;

                $toolRepository = app(ToolRepository::class);
                $capabilities->withTools(['schemas' => $toolRepository->getToolSchemas()]);

                $resourceRepository = app(\OPGG\LaravelMcpServer\Services\ResourceService\ResourceRepository::class);
                $capabilities->withResources(['resources' => $resourceRepository->getResourceMetadatas(), 'resourceTemplates' => $resourceRepository->getTemplateMetadatas()]);

                return MCPServer::create(protocol: $protocol, name: $serverInfo['name'], version: $serverInfo['version'], capabilities: $capabilities)
                    ->registerToolRepository(toolRepository: $toolRepository)
                    ->registerResourceRepository(resourceRepository: $resourceRepository);
            });
        }
    }
}
