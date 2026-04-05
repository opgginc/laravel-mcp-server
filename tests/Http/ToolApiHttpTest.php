<?php

use Illuminate\Support\Facades\Route;
use OPGG\LaravelMcpServer\Data\ToolResolutionContext;
use OPGG\LaravelMcpServer\Routing\McpEndpointDefinition;
use OPGG\LaravelMcpServer\Services\ToolService\DynamicToolResolverInterface;
use OPGG\LaravelMcpServer\Tests\Fixtures\Resolvers\ConstructionCountingToolResolver;
use OPGG\LaravelMcpServer\Tests\Fixtures\Resolvers\PhaseToolResolver;
use OPGG\LaravelMcpServer\Tests\Fixtures\Resolvers\ToolApiPhaseToolResolver;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\ConstructionCounterTool;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\LegacyArrayTool;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\SharedNamePrimaryTool;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\SharedNameSecondaryTool;

function registerToolApiEndpoint(array $tools, string $path = '/mcp'): void
{
    Route::mcp($path)
        ->setServerInfo(
            name: 'Tool API Test MCP',
            version: '1.0.0',
        )
        ->enabledApi()
        ->tools($tools);
}

test('tool can be called via /tools/{tool_name} when enabledApi is enabled', function () {
    registerToolApiEndpoint([LegacyArrayTool::class]);

    $response = $this->postJson('/tools/legacy-array-tool', [
        'name' => 'tester',
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('status', 'ok');
    $response->assertJsonPath('echo.name', 'tester');
});

test('tool can be called via form data on /tools/{tool_name}', function () {
    registerToolApiEndpoint([LegacyArrayTool::class]);

    $response = $this->post('/tools/legacy-array-tool', [
        'name' => 'form-tester',
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('status', 'ok');
    $response->assertJsonPath('echo.name', 'form-tester');
});

test('tool can be called via query parameters on /tools/{tool_name}', function () {
    registerToolApiEndpoint([LegacyArrayTool::class]);

    $response = $this->post('/tools/legacy-array-tool?name=query-tester');

    $response->assertStatus(200);
    $response->assertJsonPath('status', 'ok');
    $response->assertJsonPath('echo.name', 'query-tester');
});

test('query parameters take precedence over json body on /tools/{tool_name}', function () {
    registerToolApiEndpoint([LegacyArrayTool::class]);

    $response = $this->call(
        method: 'POST',
        uri: '/tools/legacy-array-tool?name=query-tester',
        parameters: [],
        cookies: [],
        files: [],
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ],
        content: '{"name":"body-tester"}',
    );

    $response->assertStatus(200);
    $response->assertJsonPath('status', 'ok');
    $response->assertJsonPath('echo.name', 'query-tester');
});

test('query parameters take precedence over malformed json body on /tools/{tool_name}', function () {
    registerToolApiEndpoint([LegacyArrayTool::class]);

    $response = $this->call(
        method: 'POST',
        uri: '/tools/legacy-array-tool?name=query-tester',
        parameters: [],
        cookies: [],
        files: [],
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ],
        content: '{"invalid":',
    );

    $response->assertStatus(200);
    $response->assertJsonPath('status', 'ok');
    $response->assertJsonPath('echo.name', 'query-tester');
});

test('repeated query parameters are normalized to arrays on /tools/{tool_name}', function () {
    registerToolApiEndpoint([LegacyArrayTool::class]);

    $response = $this->post('/tools/legacy-array-tool?desired_output_fields=items&desired_output_fields=runes');

    $response->assertStatus(200);
    $response->assertJsonPath('status', 'ok');
    $response->assertJsonPath('echo.desired_output_fields.0', 'items');
    $response->assertJsonPath('echo.desired_output_fields.1', 'runes');
});

test('tool api route returns 404 when tool name is unknown', function () {
    registerToolApiEndpoint([LegacyArrayTool::class]);

    $response = $this->postJson('/tools/unknown-tool', []);

    $response->assertStatus(404);
    $response->assertJsonPath('code', -32601);
});

test('tool api route keeps filter query separate from tool arguments', function () {
    Route::mcp('/filtered-body-mcp')
        ->setServerInfo(
            name: 'Filtered Tool API Body Test MCP',
            version: '1.0.0',
        )
        ->enabledApi()
        ->dynamicTools(ToolApiPhaseToolResolver::class);

    $response = $this->postJson('/tools/legacy-array-tool?phase=lobby&name=query-tester', [
        'region' => 'NA',
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('status', 'ok');
    $response->assertJsonPath('echo.name', 'query-tester');
    $response->assertJsonPath('echo.region', 'NA');
    expect($response->json('echo'))->not->toHaveKey('phase');
});

test('tool api route returns parse error when only filter query parameters are present', function () {
    Route::mcp('/filtered-body-mcp')
        ->setServerInfo(
            name: 'Filtered Tool API Body Test MCP',
            version: '1.0.0',
        )
        ->enabledApi()
        ->dynamicTools(ToolApiPhaseToolResolver::class);

    $response = $this->call(
        method: 'POST',
        uri: '/tools/legacy-array-tool?phase=lobby',
        parameters: [],
        cookies: [],
        files: [],
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ],
        content: '{"invalid":',
    );

    $response->assertStatus(400);
    $response->assertJsonPath('code', -32700);
});

test('tool api route keeps parse errors for unknown tools even when only filter query parameters are present', function () {
    Route::mcp('/filtered-body-mcp')
        ->setServerInfo(
            name: 'Filtered Tool API Body Test MCP',
            version: '1.0.0',
        )
        ->enabledApi()
        ->dynamicTools(ToolApiPhaseToolResolver::class);

    $response = $this->call(
        method: 'POST',
        uri: '/tools/unknown-tool?phase=lobby',
        parameters: [],
        cookies: [],
        files: [],
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ],
        content: '{"invalid":',
    );

    $response->assertStatus(400);
    $response->assertJsonPath('code', -32700);
});

test('tool api route forwards filter query parameters when resolver omits the hook', function () {
    Route::mcp('/filtered-body-no-hook-mcp')
        ->setServerInfo(
            name: 'Filtered Tool API No Hook Test MCP',
            version: '1.0.0',
        )
        ->enabledApi()
        ->dynamicTools(PhaseToolResolver::class);

    $response = $this->postJson('/tools/legacy-array-tool?phase=lobby&name=query-tester', [
        'region' => 'NA',
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('status', 'ok');
    $response->assertJsonPath('echo.phase', 'lobby');
    $response->assertJsonPath('echo.name', 'query-tester');
    $response->assertJsonPath('echo.region', 'NA');
});

test('tool api route reuses a dynamic resolver instance across filtering and execution', function () {
    ConstructionCountingToolResolver::resetCounter();
    ConstructionCounterTool::resetCounter();

    Route::mcp('/construction-count-mcp')
        ->setServerInfo(
            name: 'Construction Count Tool API Test MCP',
            version: '1.0.0',
        )
        ->enabledApi()
        ->dynamicTools(ConstructionCountingToolResolver::class);

    $response = $this->postJson('/tools/construction-counter-tool?phase=lobby', []);

    $response->assertStatus(200);
    $response->assertJsonPath('content.0.text', 'ok');
    expect(ConstructionCountingToolResolver::$constructionCount)->toBe(1);
});

test('tool api route respects query string tool filtering', function () {
    Route::mcp('/filtered-mcp')
        ->setServerInfo(
            name: 'Filtered Tool API Test MCP',
            version: '1.0.0',
        )
        ->enabledApi()
        ->dynamicTools(PhaseToolResolver::class);

    $visibleResponse = $this->post('/tools/structured-only-tool?phase=inprogress&region=NA');

    $visibleResponse->assertStatus(200);
    $visibleResponse->assertJsonPath('structuredContent.status', 'ok');
    $visibleResponse->assertJsonPath('structuredContent.region', 'NA');

    $hiddenResponse = $this->postJson('/tools/legacy-array-tool?phase=inprogress', []);

    $hiddenResponse->assertStatus(404);
    $hiddenResponse->assertJsonPath('code', -32601);
});

test('filtered tool does not fall through to a later endpoint with the same tool name', function () {
    $resolver = new class implements DynamicToolResolverInterface
    {
        public function declaredTools(McpEndpointDefinition $endpoint): array
        {
            return [SharedNamePrimaryTool::class];
        }

        public function resolve(McpEndpointDefinition $endpoint, ToolResolutionContext $context): array
        {
            return match ($context->queryParameters['phase'] ?? null) {
                'before' => [SharedNamePrimaryTool::class],
                default => [],
            };
        }
    };

    Route::mcp('/filtered-primary')
        ->setServerInfo(
            name: 'Filtered Primary Tool API Test MCP',
            version: '1.0.0',
        )
        ->enabledApi()
        ->dynamicTools($resolver::class);

    registerToolApiEndpoint([SharedNameSecondaryTool::class], '/secondary-mcp');

    $response = $this->postJson('/tools/shared-name-tool?phase=after', []);

    $response->assertStatus(404);
    $response->assertJsonPath('code', -32601);
});

test('first enabledApi endpoint wins when multiple endpoints have the same tool name', function () {
    registerToolApiEndpoint([SharedNamePrimaryTool::class], '/mcp-primary');
    registerToolApiEndpoint([SharedNameSecondaryTool::class], '/mcp-secondary');

    $response = $this->postJson('/tools/shared-name-tool', []);

    $response->assertStatus(200);
    $response->assertJsonPath('source', 'primary');
});

test('tool api route returns parse error for invalid json payload', function () {
    registerToolApiEndpoint([LegacyArrayTool::class]);

    $response = $this->call(
        method: 'POST',
        uri: '/tools/legacy-array-tool',
        parameters: [],
        cookies: [],
        files: [],
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ],
        content: '{"invalid":',
    );

    $response->assertStatus(400);
    $response->assertJsonPath('code', -32700);
});

test('tool api route is not registered when enabledApi is not used', function () {
    Route::mcp('/mcp')->tools([LegacyArrayTool::class]);

    $response = $this->postJson('/tools/legacy-array-tool', []);

    $response->assertStatus(404);
});
