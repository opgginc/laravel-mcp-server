<?php

use OPGG\LaravelMcpServer\Data\ToolResolutionContext;
use OPGG\LaravelMcpServer\Routing\McpEndpointDefinition;
use OPGG\LaravelMcpServer\Services\ToolService\EndpointToolCatalog;
use OPGG\LaravelMcpServer\Tests\Fixtures\Resolvers\ConstructionCountingToolResolver;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\ConstructionCounterTool;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\SecondaryConstructionCounterTool;

beforeEach(function () {
    ConstructionCounterTool::resetCounter();
    SecondaryConstructionCounterTool::resetCounter();
    ConstructionCountingToolResolver::resetCounter();
});

test('declaresToolName reuses cached tool names without reconstructing tools', function () {
    $endpoint = McpEndpointDefinition::create('static-tools', '/static-tools')
        ->withTools([ConstructionCounterTool::class]);

    $catalog = app(EndpointToolCatalog::class);

    expect($catalog->declaresToolName($endpoint, 'construction-counter-tool'))->toBeTrue();
    expect(ConstructionCounterTool::$constructionCount)->toBe(1);

    expect($catalog->declaresToolName($endpoint, 'construction-counter-tool'))->toBeTrue();
    expect(ConstructionCounterTool::$constructionCount)->toBe(1);
});

test('visibleToolClasses reuses a single dynamic resolver instance per call', function () {
    $endpoint = McpEndpointDefinition::create('dynamic-tools', '/dynamic-tools')
        ->withDynamicToolsResolver(ConstructionCountingToolResolver::class);

    $catalog = app(EndpointToolCatalog::class);

    expect($catalog->visibleToolClasses(
        $endpoint,
        new ToolResolutionContext(queryParameters: ['phase' => 'lobby']),
    ))->toBe([
        ConstructionCounterTool::class,
        SecondaryConstructionCounterTool::class,
    ]);

    expect(ConstructionCountingToolResolver::$constructionCount)->toBe(1);
});
