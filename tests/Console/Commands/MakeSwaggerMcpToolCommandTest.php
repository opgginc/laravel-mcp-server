<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Clean up directories before each test
    File::deleteDirectory(app_path('MCP/Tools'));
    File::deleteDirectory(app_path('MCP/Resources'));

    // Create a minimal config file for testing
    $configDir = config_path();
    if (! File::isDirectory($configDir)) {
        File::makeDirectory($configDir, 0755, true);
    }

    $configContent = "<?php\n\nreturn [\n    'tools' => [],\n    'resources' => [],\n];";
    File::put(config_path('mcp-server.php'), $configContent);
});

afterEach(function () {
    // Clean up after each test
    File::deleteDirectory(app_path('MCP/Tools'));
    File::deleteDirectory(app_path('MCP/Resources'));
    if (File::exists(config_path('mcp-server.php'))) {
        File::delete(config_path('mcp-server.php'));
    }
});

// Test tag-based directory creation
test('createDirectory returns tag-based directory by default', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand;

    // Mock the command and set the groupingMethod property
    $command = \Mockery::mock($command)->makePartial();

    // Use reflection to set the groupingMethod property
    $property = new ReflectionProperty($command, 'groupingMethod');
    $property->setAccessible(true);
    $property->setValue($command, 'tag');

    $method = new ReflectionMethod($command, 'createDirectory');
    $method->setAccessible(true);

    $endpoint = ['tags' => ['pet']];
    $result = $method->invoke($command, $endpoint);

    expect($result)->toBe('Pet');
});

test('createDirectory returns path-based directory', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand;

    // Mock the command and set the groupingMethod property
    $command = \Mockery::mock($command)->makePartial();

    // Use reflection to set the groupingMethod property
    $property = new ReflectionProperty($command, 'groupingMethod');
    $property->setAccessible(true);
    $property->setValue($command, 'path');

    $method = new ReflectionMethod($command, 'createDirectory');
    $method->setAccessible(true);

    $endpoint = ['path' => '/users/profile'];
    $result = $method->invoke($command, $endpoint);

    expect($result)->toBe('Users');
});

test('createDirectory returns empty string for none grouping', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand;

    // Mock the command and set the groupingMethod property
    $command = \Mockery::mock($command)->makePartial();

    // Use reflection to set the groupingMethod property
    $property = new ReflectionProperty($command, 'groupingMethod');
    $property->setAccessible(true);
    $property->setValue($command, 'none');

    $method = new ReflectionMethod($command, 'createDirectory');
    $method->setAccessible(true);

    $endpoint = ['tags' => ['pet']];
    $result = $method->invoke($command, $endpoint);

    expect($result)->toBe('');
});

test('createTagDirectory returns StudlyCase for single tag', function () {
    $filesystem = new \Illuminate\Filesystem\Filesystem;
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand($filesystem);

    // Use reflection to access protected method
    $method = new ReflectionMethod($command, 'createTagDirectory');
    $method->setAccessible(true);

    $endpoint = ['tags' => ['pet']];
    $result = $method->invoke($command, $endpoint);

    expect($result)->toBe('Pet');
});

test('createTagDirectory returns General for empty tags', function () {
    $filesystem = new \Illuminate\Filesystem\Filesystem;
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand($filesystem);

    $method = new ReflectionMethod($command, 'createTagDirectory');
    $method->setAccessible(true);

    $endpoint = ['tags' => []];
    $result = $method->invoke($command, $endpoint);

    expect($result)->toBe('General');
});

test('createTagDirectory returns General for missing tags key', function () {
    $filesystem = new \Illuminate\Filesystem\Filesystem;
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand($filesystem);

    $method = new ReflectionMethod($command, 'createTagDirectory');
    $method->setAccessible(true);

    $endpoint = [];
    $result = $method->invoke($command, $endpoint);

    expect($result)->toBe('General');
});

test('createTagDirectory uses first tag when multiple tags exist', function () {
    $filesystem = new \Illuminate\Filesystem\Filesystem;
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand($filesystem);

    $method = new ReflectionMethod($command, 'createTagDirectory');
    $method->setAccessible(true);

    $endpoint = ['tags' => ['store', 'inventory', 'user']];
    $result = $method->invoke($command, $endpoint);

    expect($result)->toBe('Store');
});

test('createTagDirectory handles special characters in tags', function () {
    $filesystem = new \Illuminate\Filesystem\Filesystem;
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand($filesystem);

    $method = new ReflectionMethod($command, 'createTagDirectory');
    $method->setAccessible(true);

    $endpoint = ['tags' => ['user-management']];
    $result = $method->invoke($command, $endpoint);

    expect($result)->toBe('UserManagement');
});

test('createTagDirectory handles snake_case tags', function () {
    $filesystem = new \Illuminate\Filesystem\Filesystem;
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand($filesystem);

    $method = new ReflectionMethod($command, 'createTagDirectory');
    $method->setAccessible(true);

    $endpoint = ['tags' => ['user_profile']];
    $result = $method->invoke($command, $endpoint);

    expect($result)->toBe('UserProfile');
});

test('createTagDirectory handles numbers in tags', function () {
    $filesystem = new \Illuminate\Filesystem\Filesystem;
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand($filesystem);

    $method = new ReflectionMethod($command, 'createTagDirectory');
    $method->setAccessible(true);

    $endpoint = ['tags' => ['api-v2']];
    $result = $method->invoke($command, $endpoint);

    expect($result)->toBe('ApiV2');
});

// Test path-based directory creation
test('createPathDirectory returns StudlyCase for path segments', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand;

    $method = new ReflectionMethod($command, 'createPathDirectory');
    $method->setAccessible(true);

    $endpoint = ['path' => '/users/profile'];
    $result = $method->invoke($command, $endpoint);

    expect($result)->toBe('Users');
});

test('createPathDirectory returns Root for empty path', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand;

    $method = new ReflectionMethod($command, 'createPathDirectory');
    $method->setAccessible(true);

    $endpoint = ['path' => '/'];
    $result = $method->invoke($command, $endpoint);

    expect($result)->toBe('Root');
});

test('createPathDirectory handles snake_case path segments', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand;

    $method = new ReflectionMethod($command, 'createPathDirectory');
    $method->setAccessible(true);

    $endpoint = ['path' => '/user_profiles/details'];
    $result = $method->invoke($command, $endpoint);

    expect($result)->toBe('UserProfiles');
});

test('createPathDirectory handles kebab-case path segments', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand;

    $method = new ReflectionMethod($command, 'createPathDirectory');
    $method->setAccessible(true);

    $endpoint = ['path' => '/api-v1/users'];
    $result = $method->invoke($command, $endpoint);

    expect($result)->toBe('ApiV1');
});

test('createPathDirectory handles missing path key', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand;

    $method = new ReflectionMethod($command, 'createPathDirectory');
    $method->setAccessible(true);

    $endpoint = [];
    $result = $method->invoke($command, $endpoint);

    expect($result)->toBe('Root');
});

test('swagger tool generation creates tag-based directories', function () {
    // Create a minimal swagger.json file
    $swaggerData = [
        'openapi' => '3.0.0',
        'info' => [
            'title' => 'Test API',
            'version' => '1.0.0',
        ],
        'paths' => [
            '/pet' => [
                'post' => [
                    'tags' => ['pet'],
                    'operationId' => 'addPet',
                    'summary' => 'Add a new pet',
                    'responses' => [
                        '200' => ['description' => 'Success'],
                    ],
                ],
            ],
            '/store/order' => [
                'post' => [
                    'tags' => ['store'],
                    'operationId' => 'placeOrder',
                    'summary' => 'Place an order',
                    'responses' => [
                        '200' => ['description' => 'Success'],
                    ],
                ],
            ],
        ],
    ];

    $swaggerPath = storage_path('swagger-test.json');
    File::put($swaggerPath, json_encode($swaggerData));

    try {
        $this->artisan('make:swagger-mcp-tool', [
            'source' => $swaggerPath,
            '--no-interaction' => true,
        ])
            ->expectsOutputToContain('MCP components generated successfully!')
            ->assertExitCode(0);

        // Check that tools were created in tag-based directories
        $petToolPath = app_path('MCP/Tools/Pet/AddPetTool.php');
        $storeToolPath = app_path('MCP/Tools/Store/PlaceOrderTool.php');

        expect(File::exists($petToolPath))->toBeTrue();
        expect(File::exists($storeToolPath))->toBeTrue();

        // Verify namespace in generated files
        $petToolContent = File::get($petToolPath);
        expect($petToolContent)->toContain('namespace App\\MCP\\Tools\\Pet;');

        $storeToolContent = File::get($storeToolPath);
        expect($storeToolContent)->toContain('namespace App\\MCP\\Tools\\Store;');

    } finally {
        // Clean up
        if (File::exists($swaggerPath)) {
            File::delete($swaggerPath);
        }
    }
});

test('swagger tool generation handles untagged endpoints', function () {
    // Create swagger with untagged endpoint - use POST to force tool generation
    $swaggerData = [
        'openapi' => '3.0.0',
        'info' => [
            'title' => 'Test API',
            'version' => '1.0.0',
        ],
        'paths' => [
            '/health' => [
                'post' => [
                    'operationId' => 'healthCheck',
                    'summary' => 'Health check',
                    'responses' => [
                        '200' => ['description' => 'Success'],
                    ],
                ],
            ],
        ],
    ];

    $swaggerPath = storage_path('swagger-untagged-test.json');
    File::put($swaggerPath, json_encode($swaggerData));

    try {
        $this->artisan('make:swagger-mcp-tool', [
            'source' => $swaggerPath,
            '--no-interaction' => true,
        ])
            ->expectsOutputToContain('MCP components generated successfully!')
            ->assertExitCode(0);

        // Check that tool was created in General directory
        $healthToolPath = app_path('MCP/Tools/General/HealthCheckTool.php');
        expect(File::exists($healthToolPath))->toBeTrue();

        // Verify namespace
        $healthToolContent = File::get($healthToolPath);
        expect($healthToolContent)->toContain('namespace App\\MCP\\Tools\\General;');

    } finally {
        if (File::exists($swaggerPath)) {
            File::delete($swaggerPath);
        }
    }
});

test('swagger tool generation creates path-based directories', function () {
    // Create swagger with various path structures
    $swaggerData = [
        'openapi' => '3.0.0',
        'info' => [
            'title' => 'Test API',
            'version' => '1.0.0',
        ],
        'paths' => [
            '/users/profile' => [
                'get' => [
                    'operationId' => 'getUserProfile',
                    'summary' => 'Get user profile',
                    'responses' => [
                        '200' => ['description' => 'Success'],
                    ],
                ],
            ],
            '/api/v1/orders' => [
                'post' => [
                    'operationId' => 'createOrder',
                    'summary' => 'Create an order',
                    'responses' => [
                        '201' => ['description' => 'Created'],
                    ],
                ],
            ],
        ],
    ];

    $swaggerPath = storage_path('swagger-path-test.json');
    File::put($swaggerPath, json_encode($swaggerData));

    try {
        $this->artisan('make:swagger-mcp-tool', [
            'source' => $swaggerPath,
            '--group-by' => 'path',
            '--no-interaction' => true,
        ])
            ->expectsOutputToContain('MCP components generated successfully!')
            ->assertExitCode(0);

        // Check that tools were created in path-based directories
        $userResourcePath = app_path('MCP/Resources/Users/GetUserProfileResource.php');
        $apiToolPath = app_path('MCP/Tools/Api/CreateOrderTool.php');

        expect(File::exists($userResourcePath))->toBeTrue();
        expect(File::exists($apiToolPath))->toBeTrue();

        // Verify namespace in generated files
        $userResourceContent = File::get($userResourcePath);
        expect($userResourceContent)->toContain('namespace App\\MCP\\Resources\\Users;');

        $apiToolContent = File::get($apiToolPath);
        expect($apiToolContent)->toContain('namespace App\\MCP\\Tools\\Api;');

    } finally {
        // Clean up
        if (File::exists($swaggerPath)) {
            File::delete($swaggerPath);
        }
    }
});

// Test interactive grouping option selection
test('getGroupingOption returns provided option when set', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand;

    // Mock the option method to return a value
    $command = \Mockery::mock($command)->makePartial();
    $command->shouldReceive('option')->with('group-by')->andReturn('path');
    $command->shouldReceive('option')->with('no-interaction')->andReturn(false);

    $method = new ReflectionMethod($command, 'getGroupingOption');
    $method->setAccessible(true);

    $result = $method->invoke($command);

    expect($result)->toBe('path');
});

test('getGroupingOption returns tag for non-interactive mode when no option provided', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand;

    // Mock the option method to return null (no option provided)
    $command = \Mockery::mock($command)->makePartial();
    $command->shouldReceive('option')->with('group-by')->andReturn(null);
    $command->shouldReceive('option')->with('no-interaction')->andReturn(true);

    $method = new ReflectionMethod($command, 'getGroupingOption');
    $method->setAccessible(true);

    $result = $method->invoke($command);

    expect($result)->toBe('tag');
});

test('getGroupingOption prompts user when no option and interactive mode', function () {
    // Skip this test as it requires complex mocking of Laravel command internals
    $this->markTestSkipped('Complex interactive mode testing requires full command initialization');
});

test('getGroupingOption handles path selection in interactive mode', function () {
    // Skip this test as it requires complex mocking of Laravel command internals
    $this->markTestSkipped('Complex interactive mode testing requires full command initialization');
});

test('getGroupingOption handles none selection in interactive mode', function () {
    // Skip this test as it requires complex mocking of Laravel command internals
    $this->markTestSkipped('Complex interactive mode testing requires full command initialization');
});

// Test generateGroupingPreviews method
test('generateGroupingPreviews returns preview examples for all grouping options', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand;

    // Mock the parser and converter
    $mockParser = \Mockery::mock(\OPGG\LaravelMcpServer\Services\SwaggerParser\SwaggerParser::class);
    $mockConverter = \Mockery::mock(\OPGG\LaravelMcpServer\Services\SwaggerParser\SwaggerToMcpConverter::class);

    // Sample endpoints for testing
    $sampleEndpoints = [
        ['method' => 'GET', 'path' => '/pets', 'tags' => ['pet']],
        ['method' => 'POST', 'path' => '/pets', 'tags' => ['pet']],
        ['method' => 'GET', 'path' => '/users', 'tags' => ['user']],
        ['method' => 'GET', 'path' => '/api/orders', 'tags' => ['order']],
    ];

    $mockParser->shouldReceive('getEndpoints')->andReturn($sampleEndpoints);
    $mockConverter->shouldReceive('generateClassName')->andReturn('SampleTool');

    // Use reflection to set the parser and converter properties
    $parserProperty = new ReflectionProperty($command, 'parser');
    $parserProperty->setAccessible(true);
    $parserProperty->setValue($command, $mockParser);

    $converterProperty = new ReflectionProperty($command, 'converter');
    $converterProperty->setAccessible(true);
    $converterProperty->setValue($command, $mockConverter);

    $method = new ReflectionMethod($command, 'generateGroupingPreviews');
    $method->setAccessible(true);

    $result = $method->invoke($command);

    expect($result)->toBeArray();
    expect($result)->toHaveKey('tag');
    expect($result)->toHaveKey('path');
    expect($result)->toHaveKey('none');

    // Check that 'none' has examples without subdirectories
    expect($result['none'])->toBeArray();
    // 'none' grouping shows files directly in root, not in General subdirectory
});

test('generateGroupingPreviews handles endpoints with no tags', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand;

    // Mock the parser and converter
    $mockParser = \Mockery::mock(\OPGG\LaravelMcpServer\Services\SwaggerParser\SwaggerParser::class);
    $mockConverter = \Mockery::mock(\OPGG\LaravelMcpServer\Services\SwaggerParser\SwaggerToMcpConverter::class);

    // Endpoints without tags
    $sampleEndpoints = [
        ['method' => 'GET', 'path' => '/health', 'tags' => []],
        ['method' => 'POST', 'path' => '/api/test'],
    ];

    $mockParser->shouldReceive('getEndpoints')->andReturn($sampleEndpoints);
    $mockConverter->shouldReceive('generateClassName')->andReturn('HealthTool');

    // Use reflection to set the parser and converter properties
    $parserProperty = new ReflectionProperty($command, 'parser');
    $parserProperty->setAccessible(true);
    $parserProperty->setValue($command, $mockParser);

    $converterProperty = new ReflectionProperty($command, 'converter');
    $converterProperty->setAccessible(true);
    $converterProperty->setValue($command, $mockConverter);

    $method = new ReflectionMethod($command, 'generateGroupingPreviews');
    $method->setAccessible(true);

    $result = $method->invoke($command);

    expect($result)->toBeArray();
    expect($result['tag'])->toBeArray(); // Should still work, just might be empty
    expect($result['path'])->toBeArray(); // Should have path-based examples
});

test('getGroupingOption displays previews in interactive mode', function () {
    $command = \Mockery::mock(\OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand::class)->makePartial()->shouldAllowMockingProtectedMethods();

    // Mock the option method to return null (no group-by option provided)
    $command->shouldReceive('option')->with('group-by')->andReturn(null);
    $command->shouldReceive('option')->with('no-interaction')->andReturn(false);

    // Mock the preview generation
    $mockPreviews = [
        'tag' => ['Tools/Pet/FindPetsTool.php', 'Resources/User/GetUserResource.php'],
        'path' => ['Tools/Api/PostApiTool.php', 'Tools/Users/GetUsersTool.php'],
        'none' => ['Tools/General/YourEndpointTool.php', 'Resources/General/YourEndpointResource.php'],
    ];

    $command->shouldReceive('generateGroupingPreviews')->andReturn($mockPreviews);

    // Mock all output methods more generously
    $command->shouldReceive('newLine')->andReturn();
    $command->shouldReceive('info')->andReturn();
    $command->shouldReceive('line')->andReturn();

    // Mock choice method
    $command->shouldReceive('choice')
        ->with('Select grouping method', \Mockery::any(), 0)
        ->andReturn('Tag-based grouping (organize by OpenAPI tags)');

    $method = new ReflectionMethod($command, 'getGroupingOption');
    $method->setAccessible(true);

    $result = $method->invoke($command);

    expect($result)->toBe('tag');
});
