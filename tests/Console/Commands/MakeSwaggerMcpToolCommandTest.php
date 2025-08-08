<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    // Clean up directories before each test
    File::deleteDirectory(app_path('MCP/Tools'));
    
    // Create a minimal config file for testing
    $configDir = config_path();
    if (!File::isDirectory($configDir)) {
        File::makeDirectory($configDir, 0755, true);
    }
    
    $configContent = "<?php\n\nreturn [\n    'tools' => [],\n    'resources' => [],\n];";
    File::put(config_path('mcp-server.php'), $configContent);
});

afterEach(function () {
    // Clean up after each test
    File::deleteDirectory(app_path('MCP/Tools'));
    if (File::exists(config_path('mcp-server.php'))) {
        File::delete(config_path('mcp-server.php'));
    }
});

test('createTagDirectory returns StudlyCase for single tag', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand();
    
    // Use reflection to access protected method
    $method = new ReflectionMethod($command, 'createTagDirectory');
    $method->setAccessible(true);
    
    $endpoint = ['tags' => ['pet']];
    $result = $method->invoke($command, $endpoint);
    
    expect($result)->toBe('Pet');
});

test('createTagDirectory returns General for empty tags', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand();
    
    $method = new ReflectionMethod($command, 'createTagDirectory');
    $method->setAccessible(true);
    
    $endpoint = ['tags' => []];
    $result = $method->invoke($command, $endpoint);
    
    expect($result)->toBe('General');
});

test('createTagDirectory returns General for missing tags key', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand();
    
    $method = new ReflectionMethod($command, 'createTagDirectory');
    $method->setAccessible(true);
    
    $endpoint = [];
    $result = $method->invoke($command, $endpoint);
    
    expect($result)->toBe('General');
});

test('createTagDirectory uses first tag when multiple tags exist', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand();
    
    $method = new ReflectionMethod($command, 'createTagDirectory');
    $method->setAccessible(true);
    
    $endpoint = ['tags' => ['store', 'inventory', 'user']];
    $result = $method->invoke($command, $endpoint);
    
    expect($result)->toBe('Store');
});

test('createTagDirectory handles special characters in tags', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand();
    
    $method = new ReflectionMethod($command, 'createTagDirectory');
    $method->setAccessible(true);
    
    $endpoint = ['tags' => ['user-management']];
    $result = $method->invoke($command, $endpoint);
    
    expect($result)->toBe('UserManagement');
});

test('createTagDirectory handles snake_case tags', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand();
    
    $method = new ReflectionMethod($command, 'createTagDirectory');
    $method->setAccessible(true);
    
    $endpoint = ['tags' => ['user_profile']];
    $result = $method->invoke($command, $endpoint);
    
    expect($result)->toBe('UserProfile');
});

test('createTagDirectory handles numbers in tags', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand();
    
    $method = new ReflectionMethod($command, 'createTagDirectory');
    $method->setAccessible(true);
    
    $endpoint = ['tags' => ['api-v2']];
    $result = $method->invoke($command, $endpoint);
    
    expect($result)->toBe('ApiV2');
});

test('swagger tool generation creates tag-based directories', function () {
    // Create a minimal swagger.json file
    $swaggerData = [
        'openapi' => '3.0.0',
        'info' => [
            'title' => 'Test API',
            'version' => '1.0.0'
        ],
        'paths' => [
            '/pet' => [
                'post' => [
                    'tags' => ['pet'],
                    'operationId' => 'addPet',
                    'summary' => 'Add a new pet',
                    'responses' => [
                        '200' => ['description' => 'Success']
                    ]
                ]
            ],
            '/store/order' => [
                'post' => [
                    'tags' => ['store'],
                    'operationId' => 'placeOrder',
                    'summary' => 'Place an order',
                    'responses' => [
                        '200' => ['description' => 'Success']
                    ]
                ]
            ]
        ]
    ];
    
    $swaggerPath = storage_path('swagger-test.json');
    File::put($swaggerPath, json_encode($swaggerData));
    
    try {
        $this->artisan('make:swagger-mcp-tools', [
            'swagger_file' => $swaggerPath,
            '--force' => true
        ])
        ->expectsOutputToContain('Tools generated successfully!')
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
            'version' => '1.0.0'
        ],
        'paths' => [
            '/health' => [
                'get' => [
                    'operationId' => 'healthCheck',
                    'summary' => 'Health check',
                    'responses' => [
                        '200' => ['description' => 'Success']
                    ]
                ]
            ]
        ]
    ];
    
    $swaggerPath = storage_path('swagger-untagged-test.json');
    File::put($swaggerPath, json_encode($swaggerData));
    
    try {
        $this->artisan('make:swagger-mcp-tools', [
            'swagger_file' => $swaggerPath,
            '--force' => true
        ])
        ->expectsOutputToContain('Tools generated successfully!')
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