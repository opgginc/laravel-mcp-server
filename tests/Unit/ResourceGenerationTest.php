<?php

use OPGG\LaravelMcpServer\Services\SwaggerParser\SwaggerParser;
use OPGG\LaravelMcpServer\Services\SwaggerParser\SwaggerToMcpConverter;

beforeEach(function () {
    $parser = new SwaggerParser;
    $this->converter = new SwaggerToMcpConverter($parser);
});

test('generates resource class names correctly', function ($path, $operationId, $expected) {
    $endpoint = [
        'path' => $path,
        'method' => 'GET',
        'operationId' => $operationId,
    ];

    $className = $this->converter->generateResourceClassName($endpoint);
    expect($className)->toBe($expected);
})->with([
    // Path-based naming (no operationId)
    ['/lol/{region}/server-stats', null, 'LolRegionServerStatsResource'],
    ['/api/users', null, 'ApiUsersResource'],
    ['/users/{id}', null, 'UsersIdResource'],

    // With proper operationId
    ['/users', 'getUsers', 'GetUsersResource'],
    ['/posts/{id}', 'getPostById', 'GetPostByIdResource'],

    // With hash operationId (should use path-based naming)
    ['/api/data', '5784a7dfd226e1621b0e6ee8c4f39407', 'ApiDataResource'],
]);

test('converts endpoint to resource with correct URI', function () {
    $parser = new SwaggerParser;
    $converter = new SwaggerToMcpConverter($parser);

    $endpoint = [
        'path' => '/api/users/{id}',
        'method' => 'GET',
        'operationId' => 'getUserById',
        'summary' => 'Get user by ID',
        'description' => 'Returns a single user',
        'parameters' => [
            ['name' => 'id', 'in' => 'path', 'required' => true, 'type' => 'integer'],
        ],
        'deprecated' => false,
        'tags' => ['users'],
        'requestBody' => null,
        'responses' => [],
        'security' => [],
    ];

    $resourceParams = $converter->convertEndpointToResource($endpoint, 'GetUserByIdResource');

    expect($resourceParams)->toHaveKeys(['className', 'uri', 'name', 'description', 'mimeType', 'readLogic']);
    expect($resourceParams['className'])->toBe('GetUserByIdResource');
    expect($resourceParams['uri'])->toBe('api://users/{id}');
    expect($resourceParams['name'])->toBe('Get user by ID');
    expect($resourceParams['mimeType'])->toBe('application/json');
});

test('generates resource URI correctly', function ($path, $expectedUri) {
    $parser = new SwaggerParser;
    $converter = new SwaggerToMcpConverter($parser);

    $endpoint = [
        'path' => $path,
        'method' => 'GET',
        'operationId' => null,
        'summary' => '',
        'description' => '',
        'parameters' => [],
        'deprecated' => false,
        'tags' => [],
        'requestBody' => null,
        'responses' => [],
        'security' => [],
    ];
    $resourceParams = $converter->convertEndpointToResource($endpoint, 'TestResource');

    expect($resourceParams['uri'])->toBe($expectedUri);
})->with([
    ['/api/users', 'api://users'],
    ['/users/{id}', 'api://users/{id}'],
    ['/api/posts/{postId}/comments', 'api://posts/{postId}/comments'],
    ['/data', 'api://data'],
]);

test('includes authentication in resource read logic', function () {
    $parser = new SwaggerParser;
    $converter = new SwaggerToMcpConverter($parser);

    // Set auth config
    $converter->setAuthConfig([
        'bearer_token' => true,
        'api_key' => ['location' => 'header', 'name' => 'X-API-Key'],
    ]);

    $endpoint = [
        'path' => '/api/protected',
        'method' => 'GET',
        'operationId' => null,
        'summary' => 'Protected endpoint',
        'description' => '',
        'parameters' => [],
        'deprecated' => false,
        'tags' => [],
        'requestBody' => null,
        'responses' => [],
        'security' => [['bearerAuth' => []]],
    ];

    $resourceParams = $converter->convertEndpointToResource($endpoint, 'ProtectedResource');

    expect($resourceParams['readLogic'])->toContain("\$headers['Authorization'] = 'Bearer '");
    expect($resourceParams['readLogic'])->toContain("\$headers['X-API-Key'] = config('services.api.key')");
});
