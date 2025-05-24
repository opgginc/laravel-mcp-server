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
 * Streamable HTTP Service Provider.
 *
 * Registers the MCPServer when `server_provider` config is set to
 * `streamable_http`. Internally it uses the existing SSE transport to
 * deliver streamed messages.
 */
final class StreamableHttpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (Config::get('mcp-server.server_provider') === 'streamable_http') {
            $this->app->singleton(ToolRepository::class, function ($app) {
                $toolRepository = new ToolRepository($app);

                $tools = Config::get('mcp-server.tools', []);
                $toolRepository->registerMany($tools);

                return $toolRepository;
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

                return MCPServer::create(
                    protocol: $protocol,
                    name: $serverInfo['name'],
                    version: $serverInfo['version'],
                    capabilities: $capabilities
                )->registerToolRepository(toolRepository: $toolRepository);
            });
        }
    }
}
