<?php

namespace OPGG\LaravelMcpServer\Tests\Fixtures\Resolvers;

use OPGG\LaravelMcpServer\Data\ToolResolutionContext;
use OPGG\LaravelMcpServer\Routing\McpEndpointDefinition;
use OPGG\LaravelMcpServer\Services\ToolService\DynamicToolResolverInterface;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\LegacyArrayTool;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\StructuredOnlyTool;

class ToolApiPhaseToolResolver implements DynamicToolResolverInterface
{
    public function declaredTools(McpEndpointDefinition $endpoint): array
    {
        return [
            LegacyArrayTool::class,
            StructuredOnlyTool::class,
        ];
    }

    public function resolve(McpEndpointDefinition $endpoint, ToolResolutionContext $context): array
    {
        return match ($context->queryParameters['phase'] ?? null) {
            'lobby' => [LegacyArrayTool::class],
            'inprogress' => [StructuredOnlyTool::class],
            default => $this->declaredTools($endpoint),
        };
    }

    /**
     * @return array<int, string>
     */
    public function consumedQueryParameters(): array
    {
        return ['phase'];
    }
}
