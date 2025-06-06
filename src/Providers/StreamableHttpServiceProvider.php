<?php

namespace OPGG\LaravelMcpServer\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use OPGG\LaravelMcpServer\Protocol\MCPProtocol;
use OPGG\LaravelMcpServer\Server\MCPServer;
use OPGG\LaravelMcpServer\Server\ServerCapabilities;
use OPGG\LaravelMcpServer\Services\PromptService\PromptRepository;
use OPGG\LaravelMcpServer\Services\ResourceService\ResourceRepository;
use OPGG\LaravelMcpServer\Services\ToolService\ToolRepository;
use OPGG\LaravelMcpServer\Transports\StreamableHttpTransport;

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

            $this->app->singleton(ResourceRepository::class, function ($app) {
                $repo = new ResourceRepository($app);
                $repo->registerResources(Config::get('mcp-server.resources', []));
                $repo->registerResourceTemplates(Config::get('mcp-server.resource_templates', []));

                return $repo;
            });

            $this->app->singleton(PromptRepository::class, function ($app) {
                $repo = new PromptRepository($app);
                $repo->registerPrompts(Config::get('mcp-server.prompts', []));

                return $repo;
            });

            $this->app->singleton(MCPServer::class, function ($app) {
                $transport = new StreamableHttpTransport;

                $protocol = new MCPProtocol($transport);

                $serverInfo = Config::get('mcp-server.server');

                $capabilities = new ServerCapabilities;

                $toolRepository = app(ToolRepository::class);
                $capabilities->withTools(['schemas' => $toolRepository->getToolSchemas()]);
                $resourceRepository = app(ResourceRepository::class);
                $capabilities->withResources(['schemas' => [
                    'resources' => $resourceRepository->getResourceSchemas(),
                    'resourceTemplates' => $resourceRepository->getTemplateSchemas(),
                ]]);
                $promptRepository = app(PromptRepository::class);
                $capabilities->withPrompts(['schemas' => [
                    'prompts' => $promptRepository->getPromptSchemas(),
                ]]);

                return MCPServer::create(protocol: $protocol, name: $serverInfo['name'], version: $serverInfo['version'], capabilities: $capabilities)
                    ->registerToolRepository(toolRepository: $toolRepository)
                    ->registerResourceRepository(repository: $resourceRepository)
                    ->registerPromptRepository(repository: $promptRepository);
            });
        }
    }
}
