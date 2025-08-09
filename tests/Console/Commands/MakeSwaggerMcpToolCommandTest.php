<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Mockery;

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

    // Mock the option method to return 'tag'
    $command = Mockery::mock($command)->makePartial();
    $command->shouldReceive('option')->with('group-by')->andReturn('tag');

    $method = new ReflectionMethod($command, 'createDirectory');
    $method->setAccessible(true);

    $endpoint = ['tags' => ['pet']];
    $result = $method->invoke($command, $endpoint);

    expect($result)->toBe('Pet');
});

test('createDirectory returns path-based directory', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand;

    // Mock the option method to return 'path'
    $command = Mockery::mock($command)->makePartial();
    $command->shouldReceive('option')->with('group-by')->andReturn('path');

    $method = new ReflectionMethod($command, 'createDirectory');
    $method->setAccessible(true);

    $endpoint = ['path' => '/users/profile'];
    $result = $method->invoke($command, $endpoint);

    expect($result)->toBe('Users');
});

test('createTagDirectory returns StudlyCase for single tag', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand;

    // Use reflection to access protected method
    $method = new ReflectionMethod($command, 'createTagDirectory');
    $method->setAccessible(true);

    $endpoint = ['tags' => ['pet']];
    $result = $method->invoke($command, $endpoint);

    expect($result)->toBe('Pet');
});

test('createTagDirectory returns General for empty tags', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand;

    $method = new ReflectionMethod($command, 'createTagDirectory');
    $method->setAccessible(true);

    $endpoint = ['tags' => []];
    $result = $method->invoke($command, $endpoint);

    expect($result)->toBe('General');
});

test('createTagDirectory returns General for missing tags key', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand;

    $method = new ReflectionMethod($command, 'createTagDirectory');
    $method->setAccessible(true);

    $endpoint = [];
    $result = $method->invoke($command, $endpoint);

    expect($result)->toBe('General');
});

test('createTagDirectory uses first tag when multiple tags exist', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand;

    $method = new ReflectionMethod($command, 'createTagDirectory');
    $method->setAccessible(true);

    $endpoint = ['tags' => ['store', 'inventory', 'user']];
    $result = $method->invoke($command, $endpoint);

    expect($result)->toBe('Store');
});

test('createTagDirectory handles special characters in tags', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand;

    $method = new ReflectionMethod($command, 'createTagDirectory');
    $method->setAccessible(true);

    $endpoint = ['tags' => ['user-management']];
    $result = $method->invoke($command, $endpoint);

    expect($result)->toBe('UserManagement');
});

test('createTagDirectory handles snake_case tags', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand;

    $method = new ReflectionMethod($command, 'createTagDirectory');
    $method->setAccessible(true);

    $endpoint = ['tags' => ['user_profile']];
    $result = $method->invoke($command, $endpoint);

    expect($result)->toBe('UserProfile');
});

test('createTagDirectory handles numbers in tags', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand;

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
    // Create swagger with untagged endpoint
    $swaggerData = [
        'openapi' => '3.0.0',
        'info' => [
            'title' => 'Test API',
            'version' => '1.0.0',
        ],
        'paths' => [
            '/health' => [
                'get' => [
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
