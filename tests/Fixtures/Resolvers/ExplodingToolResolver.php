<?php

namespace OPGG\LaravelMcpServer\Tests\Fixtures\Resolvers;

use LogicException;
use OPGG\LaravelMcpServer\Data\ToolResolutionContext;
use OPGG\LaravelMcpServer\Routing\McpEndpointDefinition;
use OPGG\LaravelMcpServer\Services\ToolService\DynamicToolResolverInterface;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\LegacyArrayTool;

class ExplodingToolResolver implements DynamicToolResolverInterface
{
    public function declaredTools(McpEndpointDefinition $endpoint): array
    {
        return [
            LegacyArrayTool::class,
        ];
    }

    public function resolve(McpEndpointDefinition $endpoint, ToolResolutionContext $context): array
    {
        throw new LogicException('Dynamic tool resolver should not run for non-tools requests.');
    }
}
