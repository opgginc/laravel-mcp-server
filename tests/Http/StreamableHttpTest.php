<?php

use Illuminate\Support\Facades\Route;
use OPGG\LaravelMcpServer\Http\Controllers\StreamableHttpController;
use OPGG\LaravelMcpServer\Routing\McpRouteRegistrar;
use OPGG\LaravelMcpServer\Services\ToolService\Examples\HelloWorldTool;
use OPGG\LaravelMcpServer\Services\ToolService\Examples\VersionCheckTool;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\AutoStructuredArrayTool;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\ConstructionCounterTool;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\LegacyArrayTool;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\MetadataAwareTool;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\SecondaryConstructionCounterTool;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\StructuredOnlyTool;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\TabularChampionsTool;

function registerMcpEndpoint(array $tools, int $pageSize = 50): void
{
    Route::mcp('/mcp')
        ->setName('HTTP Test MCP')
        ->setVersion('1.0.0')
        ->tools($tools)
        ->toolsPageSize($pageSize);
}

function defaultTools(): array
{
    return [
        HelloWorldTool::class,
        VersionCheckTool::class,
    ];
}

test('streamable http GET returns method not allowed', function () {
    registerMcpEndpoint(defaultTools());

    $response = $this->get('/mcp');

    $response->assertStatus(405)
        ->assertJson([
            'jsonrpc' => '2.0',
            'error' => 'Method Not Allowed',
        ]);
});

test('tool can be called via streamable http', function () {
    registerMcpEndpoint(defaultTools());

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => [
            'name' => 'hello-world',
            'arguments' => [
                'name' => 'Tester',
            ],
        ],
    ];

    $response = $this->postJson('/mcp', $payload);

    $response->assertStatus(200);
    $data = $response->json();

    expect($data['jsonrpc'])->toBe('2.0');
    expect($data['id'])->toBe(1);
    expect($data['result']['content'][0]['type'])->toBe('text');
    expect($data['result']['content'][0]['text'])
        ->toContain('HelloWorld `Tester` developer');

    expect($data['result']['content'])->toHaveCount(1);
    expect($data['result']['structuredContent']['message'])
        ->toContain('HelloWorld `Tester` developer');
});

test('initialize does not instantiate tool classes for non-tool requests', function () {
    ConstructionCounterTool::resetCounter();
    registerMcpEndpoint([ConstructionCounterTool::class]);

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 901,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2025-11-25',
            'capabilities' => [],
            'clientInfo' => [
                'name' => 'perf-test-client',
                'version' => '1.0.0',
            ],
        ],
    ];

    $response = $this->postJson('/mcp', $payload);
    $response->assertStatus(200);
    expect($response->json('result.protocolVersion'))->toBe('2025-11-25');
    expect(ConstructionCounterTool::$constructionCount)->toBe(0);
});

test('tool classes are instantiated when tools endpoints are requested', function () {
    ConstructionCounterTool::resetCounter();
    registerMcpEndpoint([ConstructionCounterTool::class]);

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 902,
        'method' => 'tools/call',
        'params' => [
            'name' => 'construction-counter-tool',
            'arguments' => [],
        ],
    ];

    $response = $this->postJson('/mcp', $payload);
    $response->assertStatus(200);

    expect(ConstructionCounterTool::$constructionCount)->toBeGreaterThan(0);
});

test('repeated tools/call does not instantiate unrelated tools after warmup', function () {
    ConstructionCounterTool::resetCounter();
    SecondaryConstructionCounterTool::resetCounter();
    registerMcpEndpoint([
        ConstructionCounterTool::class,
        SecondaryConstructionCounterTool::class,
    ]);

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 903,
        'method' => 'tools/call',
        'params' => [
            'name' => 'construction-counter-tool',
            'arguments' => [],
        ],
    ];

    $first = $this->postJson('/mcp', $payload);
    $first->assertStatus(200);
    $secondaryAfterFirstCall = SecondaryConstructionCounterTool::$constructionCount;

    $payload['id'] = 904;
    $second = $this->postJson('/mcp', $payload);
    $second->assertStatus(200);

    expect(SecondaryConstructionCounterTool::$constructionCount)->toBe($secondaryAfterFirstCall);
});

test('resources/list does not instantiate tool classes for non-tool requests', function () {
    ConstructionCounterTool::resetCounter();

    Route::mcp('/mcp')
        ->setName('HTTP Test MCP')
        ->setVersion('1.0.0')
        ->tools([ConstructionCounterTool::class]);

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 907,
        'method' => 'resources/list',
        'params' => [],
    ];

    $response = $this->postJson('/mcp', $payload);
    $response->assertStatus(200);
    expect($response->json('result.resources'))->toBeArray();
    expect(ConstructionCounterTool::$constructionCount)->toBe(0);
});

test('repeated tools/execute does not instantiate unrelated tools after warmup', function () {
    ConstructionCounterTool::resetCounter();
    SecondaryConstructionCounterTool::resetCounter();
    registerMcpEndpoint([
        ConstructionCounterTool::class,
        SecondaryConstructionCounterTool::class,
    ]);

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 908,
        'method' => 'tools/execute',
        'params' => [
            'name' => 'construction-counter-tool',
            'arguments' => [],
        ],
    ];

    $first = $this->postJson('/mcp', $payload);
    $first->assertStatus(200);
    expect($first->json('result.result.content.0.text'))->toBe('ok');

    $secondaryAfterFirstCall = SecondaryConstructionCounterTool::$constructionCount;

    $payload['id'] = 909;
    $second = $this->postJson('/mcp', $payload);
    $second->assertStatus(200);

    expect(SecondaryConstructionCounterTool::$constructionCount)->toBe($secondaryAfterFirstCall);
});

test('shared tool classes are not re-instantiated for warmup across endpoints', function () {
    ConstructionCounterTool::resetCounter();
    SecondaryConstructionCounterTool::resetCounter();

    $tools = [
        ConstructionCounterTool::class,
        SecondaryConstructionCounterTool::class,
    ];

    Route::mcp('/mcp-alpha')
        ->setName('alpha')
        ->setVersion('1.0.0')
        ->tools($tools);

    Route::mcp('/mcp-beta')
        ->setName('beta')
        ->setVersion('1.0.0')
        ->tools($tools);

    $alphaPayload = [
        'jsonrpc' => '2.0',
        'id' => 905,
        'method' => 'tools/call',
        'params' => [
            'name' => 'construction-counter-tool',
            'arguments' => [],
        ],
    ];
    $alphaResponse = $this->postJson('/mcp-alpha', $alphaPayload);
    $alphaResponse->assertStatus(200);

    $secondaryAfterAlpha = SecondaryConstructionCounterTool::$constructionCount;

    $betaPayload = $alphaPayload;
    $betaPayload['id'] = 906;
    $betaResponse = $this->postJson('/mcp-beta', $betaPayload);
    $betaResponse->assertStatus(200);

    expect(SecondaryConstructionCounterTool::$constructionCount)->toBe($secondaryAfterAlpha);
});

test('unknown tool lookup is cached across endpoints with shared tool classes', function () {
    ConstructionCounterTool::resetCounter();
    SecondaryConstructionCounterTool::resetCounter();

    $tools = [
        ConstructionCounterTool::class,
        SecondaryConstructionCounterTool::class,
    ];

    Route::mcp('/mcp-gamma')
        ->setName('gamma')
        ->setVersion('1.0.0')
        ->tools($tools);

    Route::mcp('/mcp-delta')
        ->setName('delta')
        ->setVersion('1.0.0')
        ->tools($tools);

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 910,
        'method' => 'tools/call',
        'params' => [
            'name' => 'unknown-tool',
            'arguments' => [],
        ],
    ];

    $gammaResponse = $this->postJson('/mcp-gamma', $payload);
    $gammaResponse->assertStatus(200);
    $gammaResponse->assertJsonPath('error.code', -32601);
    $gammaResponse->assertJsonPath('error.message', "Tool 'unknown-tool' not found");

    $secondaryAfterGamma = SecondaryConstructionCounterTool::$constructionCount;

    $payload['id'] = 911;
    $deltaResponse = $this->postJson('/mcp-delta', $payload);
    $deltaResponse->assertStatus(200);
    $deltaResponse->assertJsonPath('error.code', -32601);
    $deltaResponse->assertJsonPath('error.message', "Tool 'unknown-tool' not found");

    expect(SecondaryConstructionCounterTool::$constructionCount)->toBe($secondaryAfterGamma);
});

test('tools/list reuses class schema cache across endpoints with shared tool classes', function () {
    ConstructionCounterTool::resetCounter();
    SecondaryConstructionCounterTool::resetCounter();

    $tools = [
        ConstructionCounterTool::class,
        SecondaryConstructionCounterTool::class,
    ];

    Route::mcp('/mcp-list-alpha')
        ->setName('list-alpha')
        ->setVersion('1.0.0')
        ->tools($tools);

    Route::mcp('/mcp-list-beta')
        ->setName('list-beta')
        ->setVersion('1.0.0')
        ->tools($tools);

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 912,
        'method' => 'tools/list',
        'params' => [],
    ];

    $alphaResponse = $this->postJson('/mcp-list-alpha', $payload);
    $alphaResponse->assertStatus(200);
    expect($alphaResponse->json('result.tools'))->toBeArray();

    $constructionAfterAlpha = [
        'primary' => ConstructionCounterTool::$constructionCount,
        'secondary' => SecondaryConstructionCounterTool::$constructionCount,
    ];

    $payload['id'] = 913;
    $betaResponse = $this->postJson('/mcp-list-beta', $payload);
    $betaResponse->assertStatus(200);
    expect($betaResponse->json('result.tools'))->toBeArray();

    expect(ConstructionCounterTool::$constructionCount)->toBe($constructionAfterAlpha['primary']);
    expect(SecondaryConstructionCounterTool::$constructionCount)->toBe($constructionAfterAlpha['secondary']);
});

test('initialize returns server info and instructions aligned with MCP schema', function () {
    Route::mcp('/mcp')
        ->setServerInfo(
            name: 'Schema MCP Server',
            version: '2.0.0',
            title: 'Schema MCP',
            description: 'Server metadata for MCP initialize',
            websiteUrl: 'https://example.com/schema-mcp',
            icons: [
                ['src' => 'https://example.com/schema-icon.png', 'mimeType' => 'image/png', 'sizes' => ['512x512'], 'theme' => 'dark'],
            ],
            instructions: 'Use tools/list before tools/call.',
        )
        ->resourcesSubscribe()
        ->resourcesListChanged()
        ->promptsListChanged()
        ->tools(defaultTools());

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 99,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2025-11-25',
            'capabilities' => [
                'roots' => [
                    'listChanged' => true,
                ],
            ],
            'clientInfo' => [
                'name' => 'pest-client',
                'version' => '1.0.0',
            ],
        ],
    ];

    $response = $this->postJson('/mcp', $payload);
    $response->assertStatus(200);

    $result = $response->json('result');
    expect($result['protocolVersion'])->toBe('2025-11-25');
    expect($result['instructions'])->toBe('Use tools/list before tools/call.');

    expect($result['serverInfo'])->toMatchArray([
        'name' => 'Schema MCP Server',
        'version' => '2.0.0',
        'title' => 'Schema MCP',
        'description' => 'Server metadata for MCP initialize',
        'websiteUrl' => 'https://example.com/schema-mcp',
    ]);
    expect($result['serverInfo']['icons'])->toBe([
        ['src' => 'https://example.com/schema-icon.png', 'mimeType' => 'image/png', 'sizes' => ['512x512'], 'theme' => 'dark'],
    ]);

    expect($result['capabilities'])->toMatchArray([
        'tools' => ['listChanged' => false],
        'resources' => ['subscribe' => true, 'listChanged' => true],
        'prompts' => ['listChanged' => true],
    ]);
});

test('initialize responds with server protocol version even when client requests older version', function () {
    registerMcpEndpoint(defaultTools());

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 98,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2024-01-01',
            'capabilities' => [
                'roots' => [
                    'listChanged' => true,
                ],
            ],
            'clientInfo' => [
                'name' => 'pest-client',
                'version' => '1.0.0',
            ],
        ],
    ];

    $response = $this->postJson('/mcp', $payload);
    $response->assertStatus(200);

    expect($response->json('result.protocolVersion'))->toBe('2025-11-25');
});

test('initialize returns invalid params when required initialize fields are missing', function () {
    registerMcpEndpoint(defaultTools());

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 97,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2025-11-25',
            'capabilities' => [],
        ],
    ];

    $response = $this->postJson('/mcp', $payload);
    $response->assertStatus(200);
    $response->assertJsonPath('error.code', -32602);
    $response->assertJsonPath('error.message', 'initialize params.clientInfo is required.');
});

test('resources subscribe and unsubscribe return empty result when subscribe capability is enabled', function () {
    Route::mcp('/mcp')
        ->setName('HTTP Test MCP')
        ->setVersion('1.0.0')
        ->tools(defaultTools())
        ->resourcesSubscribe();

    $subscribePayload = [
        'jsonrpc' => '2.0',
        'id' => 95,
        'method' => 'resources/subscribe',
        'params' => [
            'uri' => 'file:///tmp/example.txt',
        ],
    ];

    $subscribeResponse = $this->postJson('/mcp', $subscribePayload);
    $subscribeResponse->assertStatus(200);
    expect($subscribeResponse->json('result'))->toBe([]);

    $unsubscribePayload = [
        'jsonrpc' => '2.0',
        'id' => 96,
        'method' => 'resources/unsubscribe',
        'params' => [
            'uri' => 'file:///tmp/example.txt',
        ],
    ];

    $unsubscribeResponse = $this->postJson('/mcp', $unsubscribePayload);
    $unsubscribeResponse->assertStatus(200);
    expect($unsubscribeResponse->json('result'))->toBe([]);
});

test('resources subscribe returns method not found when subscribe capability is disabled', function () {
    registerMcpEndpoint(defaultTools());

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 94,
        'method' => 'resources/subscribe',
        'params' => [
            'uri' => 'file:///tmp/example.txt',
        ],
    ];

    $response = $this->postJson('/mcp', $payload);
    $response->assertStatus(200);
    $response->assertJsonPath('error.code', -32601);
    $response->assertJsonPath('error.message', 'Method not found: resources/subscribe');
});

test('legacy array tool keeps payload in content by default', function () {
    registerMcpEndpoint(array_merge(defaultTools(), [LegacyArrayTool::class]));

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 12,
        'method' => 'tools/call',
        'params' => [
            'name' => 'legacy-array-tool',
            'arguments' => [
                'foo' => 'bar',
            ],
        ],
    ];

    $response = $this->postJson('/mcp', $payload);

    $response->assertStatus(200);
    $data = $response->json('result');

    expect($data)->toHaveKey('content');
    expect($data)->not->toHaveKey('structuredContent');
    expect($data['content'][0]['type'])->toBe('text');

    $decoded = json_decode($data['content'][0]['text'], true, 512, JSON_THROW_ON_ERROR);
    expect($decoded)->toBe([
        'status' => 'ok',
        'echo' => [
            'foo' => 'bar',
        ],
    ]);
});

test('tools can opt into automatic structuredContent detection', function () {
    registerMcpEndpoint(array_merge(defaultTools(), [AutoStructuredArrayTool::class]));

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 13,
        'method' => 'tools/call',
        'params' => [
            'name' => 'auto-structured-array-tool',
            'arguments' => [
                'alpha' => 'beta',
            ],
        ],
    ];

    $response = $this->postJson('/mcp', $payload);

    $response->assertStatus(200);
    $data = $response->json('result');

    expect($data)->toHaveKey('content');
    expect($data['content'][0]['type'])->toBe('text');
    expect(json_decode($data['content'][0]['text'], true, 512, JSON_THROW_ON_ERROR))->toBe([
        'status' => 'ok',
        'echo' => [
            'alpha' => 'beta',
        ],
    ]);
    expect($data)->toHaveKey('structuredContent');
    expect($data['structuredContent'])->toBe([
        'status' => 'ok',
        'echo' => [
            'alpha' => 'beta',
        ],
    ]);
});

test('toolresponse structured payload keeps required content field', function () {
    registerMcpEndpoint(array_merge(defaultTools(), [StructuredOnlyTool::class]));

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 14,
        'method' => 'tools/call',
        'params' => [
            'name' => 'structured-only-tool',
            'arguments' => [
                'region' => 'KR',
            ],
        ],
    ];

    $response = $this->postJson('/mcp', $payload);
    $response->assertStatus(200);

    $result = $response->json('result');

    expect($result['structuredContent'])->toBe([
        'status' => 'ok',
        'region' => 'KR',
    ]);
    expect($result['content'][0]['type'])->toBe('text');
    expect(json_decode($result['content'][0]['text'], true, 512, JSON_THROW_ON_ERROR))->toBe([
        'status' => 'ok',
        'region' => 'KR',
    ]);
});

test('notification returns HTTP 202 with no body', function () {
    registerMcpEndpoint(defaultTools());

    $payload = [
        'jsonrpc' => '2.0',
        'method' => 'notifications/initialized',
        'params' => [],
    ];

    $response = $this->postJson('/mcp', $payload);

    $response->assertStatus(202);
    expect($response->getContent())->toBe('');
});

test('streamable http returns bad request when endpoint metadata is missing', function () {
    Route::post('/mcp-without-endpoint', [StreamableHttpController::class, 'postHandle']);

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 20,
        'method' => 'tools/list',
        'params' => [],
    ];

    $response = $this->postJson('/mcp-without-endpoint', $payload);

    $response->assertStatus(400)
        ->assertJson([
            'jsonrpc' => '2.0',
            'error' => [
                'code' => -32600,
                'message' => 'Bad Request: MCP endpoint is not configured.',
            ],
        ]);
});

test('streamable http returns bad request when endpoint metadata points to unknown endpoint', function () {
    Route::post('/mcp-unknown-endpoint', [
        'uses' => StreamableHttpController::class.'@postHandle',
        McpRouteRegistrar::ROUTE_DEFAULT_ENDPOINT_KEY => 'unknown-endpoint-id',
    ]);

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 21,
        'method' => 'tools/list',
        'params' => [],
    ];

    $response = $this->postJson('/mcp-unknown-endpoint', $payload);

    $response->assertStatus(400)
        ->assertJson([
            'jsonrpc' => '2.0',
            'error' => [
                'code' => -32600,
                'message' => 'Bad Request: MCP endpoint is not registered.',
            ],
        ]);
});

test('streamable http returns bad request for malformed json payload', function () {
    registerMcpEndpoint(defaultTools());

    $response = $this->call(
        'POST',
        '/mcp',
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'application/json'],
        '{"jsonrpc":"2.0","id":1,"method":"tools/list","params":'
    );

    $response->assertStatus(400)
        ->assertJson([
            'jsonrpc' => '2.0',
            'error' => [
                'code' => -32700,
                'message' => 'Parse error',
            ],
        ]);
});

test('tools list includes metadata extensions when tool exposes them', function () {
    registerMcpEndpoint(array_merge(defaultTools(), [MetadataAwareTool::class]));

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 15,
        'method' => 'tools/list',
        'params' => [],
    ];

    $response = $this->postJson('/mcp', $payload);
    $response->assertStatus(200);

    $tools = $response->json('result.tools');
    $target = collect($tools)->firstWhere('name', 'metadata-aware-tool');

    expect($target)->not->toBeNull();
    expect($target['execution'])->toBe([
        'mode' => 'sync',
    ]);
    expect($target['_meta'])->toBe([
        'vendor' => 'opgg',
    ]);
});

test('tool can respond with csv content when using the tabular helpers', function () {
    registerMcpEndpoint(array_merge(defaultTools(), [TabularChampionsTool::class]));

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'tabular-champions',
            'arguments' => [
                'format' => 'csv',
            ],
        ],
    ];

    $response = $this->postJson('/mcp', $payload);

    $response->assertStatus(200);
    $data = $response->json();

    expect($data['result']['content'][0]['type'])->toBe('text/csv');
    expect($data['result']['content'][0]['text'])
        ->toContain('champion_id,key,name')
        ->toContain('1,Annie,Annie');
});

test('tool can respond with markdown table content', function () {
    registerMcpEndpoint(array_merge(defaultTools(), [TabularChampionsTool::class]));

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 3,
        'method' => 'tools/call',
        'params' => [
            'name' => 'tabular-champions',
            'arguments' => [
                'format' => 'markdown',
            ],
        ],
    ];

    $response = $this->postJson('/mcp', $payload);

    $response->assertStatus(200);
    $data = $response->json();

    expect($data['result']['content'][0]['type'])->toBe('text/markdown');
    expect($data['result']['content'][0]['text'])
        ->toContain('| champion_id | key | name |')
        ->toContain('| 1 | Annie | Annie |');
});

test('tools list endpoint supports cursor pagination', function () {
    registerMcpEndpoint(defaultTools(), pageSize: 1);

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 10,
        'method' => 'tools/list',
        'params' => [],
    ];

    $firstResponse = $this->postJson('/mcp', $payload);
    $firstResponse->assertStatus(200);
    $firstData = $firstResponse->json('result');

    expect($firstData['tools'])->toHaveCount(1);
    expect($firstData['tools'][0]['title'])->toBe('Hello World Greeting');
    expect($firstData['tools'][0]['icons'])->toBe([
        ['src' => 'https://example.com/icons/hello-world.png', 'mimeType' => 'image/png', 'sizes' => ['256x256'], 'theme' => 'light'],
    ]);
    expect($firstData)->toHaveKey('nextCursor');

    $secondPayload = $payload;
    $secondPayload['id'] = 11;
    $secondPayload['params']['cursor'] = $firstData['nextCursor'];

    $secondResponse = $this->postJson('/mcp', $secondPayload);
    $secondResponse->assertStatus(200);
    $secondData = $secondResponse->json('result');

    expect($secondData['tools'])->toHaveCount(1);
    expect($secondData)->not->toHaveKey('nextCursor');
});
