<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

use OPGG\LaravelMcpServer\Data\ToolResolutionContext;
use OPGG\LaravelMcpServer\Routing\McpEndpointDefinition;

interface DynamicToolResolverInterface
{
    /**
     * @return array<int, class-string<ToolInterface>>
     */
    public function declaredTools(McpEndpointDefinition $endpoint): array;

    /**
     * @return array<int, class-string<ToolInterface>>
     */
    public function resolve(McpEndpointDefinition $endpoint, ToolResolutionContext $context): array;
}
