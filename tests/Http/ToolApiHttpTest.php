<?php

use Illuminate\Support\Facades\Route;
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
