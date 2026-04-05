<?php

namespace OPGG\LaravelMcpServer\Tests\Fixtures\Resolvers;

use OPGG\LaravelMcpServer\Data\ToolResolutionContext;
use OPGG\LaravelMcpServer\Routing\McpEndpointDefinition;
use OPGG\LaravelMcpServer\Services\ToolService\DynamicToolResolverInterface;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\ConstructionCounterTool;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\SecondaryConstructionCounterTool;

class ConstructionCountingToolResolver implements DynamicToolResolverInterface
{
    public static int $constructionCount = 0;

    public function __construct()
    {
        self::$constructionCount++;
    }

    public static function resetCounter(): void
    {
        self::$constructionCount = 0;
    }

    public function declaredTools(McpEndpointDefinition $endpoint): array
    {
        return [
            ConstructionCounterTool::class,
            SecondaryConstructionCounterTool::class,
        ];
    }

    public function resolve(McpEndpointDefinition $endpoint, ToolResolutionContext $context): array
    {
        return $this->declaredTools($endpoint);
    }

    /**
     * @return array<int, string>
     */
    public function consumedQueryParameters(): array
    {
        return ['phase'];
    }
}
