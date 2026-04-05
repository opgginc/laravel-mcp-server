<?php

use OPGG\LaravelMcpServer\Data\ToolResolutionContext;
use OPGG\LaravelMcpServer\Routing\McpEndpointDefinition;
use OPGG\LaravelMcpServer\Server\McpServerFactory;
use OPGG\LaravelMcpServer\Tests\Fixtures\Resolvers\PhaseToolResolver;

test('dynamic endpoints do not retain per-subset tool lookup caches', function () {
    $factory = app(McpServerFactory::class);
    $endpoint = McpEndpointDefinition::create('dynamic-cache-endpoint', '/dynamic-cache')
        ->withDynamicToolsResolver(PhaseToolResolver::class);

    foreach ([
        ['phase' => 'lobby'],
        ['phase' => 'inprogress'],
        [],
    ] as $index => $queryParameters) {
        $factory->make(
            endpoint: $endpoint,
            requestMessage: [
                'jsonrpc' => '2.0',
                'id' => $index + 1,
                'method' => 'tools/call',
                'params' => [
                    'name' => 'legacy-array-tool',
                    'arguments' => [],
                ],
            ],
            toolResolutionContext: new ToolResolutionContext(queryParameters: $queryParameters),
        );
    }

    $property = new ReflectionProperty($factory, 'toolClassMapByEndpoint');
    $property->setAccessible(true);

    expect($property->getValue($factory))->toBe([]);
});
