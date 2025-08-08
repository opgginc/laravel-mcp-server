<?php

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

test('tag directory handles complex special characters', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand;

    $method = new ReflectionMethod($command, 'createTagDirectory');
    $method->setAccessible(true);

    // Test various special character combinations
    $testCases = [
        ['tags' => ['user-management-v2']] => 'UserManagementV2',
        ['tags' => ['api_v1_beta']] => 'ApiV1Beta',
        ['tags' => ['pet store']] => 'PetStore',
        ['tags' => ['user.profile']] => 'UserProfile',
        ['tags' => ['admin-panel_v2.0']] => 'AdminPanelV20',
        ['tags' => ['123-api']] => '123Api',
        ['tags' => ['user@profile']] => 'UserProfile',
        ['tags' => ['api/v1/users']] => 'ApiV1Users',
    ];

    foreach ($testCases as $input => $expected) {
        $result = $method->invoke($command, $input);
        expect($result)->toBe($expected, 'Failed for input: '.json_encode($input));
    }
});

test('tag directory handles empty strings and whitespace', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand;

    $method = new ReflectionMethod($command, 'createTagDirectory');
    $method->setAccessible(true);

    $testCases = [
        ['tags' => ['']] => 'General',
        ['tags' => ['   ']] => 'General',
        ['tags' => ["\t\n"]] => 'General',
        ['tags' => ['', 'pet']] => 'General', // First tag is empty
        ['tags' => ['   ', 'store']] => 'General', // First tag is whitespace
    ];

    foreach ($testCases as $input => $expected) {
        $result = $method->invoke($command, $input);
        expect($result)->toBe($expected, 'Failed for input: '.json_encode($input));
    }
});

test('tag directory handles unicode characters', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeSwaggerMcpToolCommand;

    $method = new ReflectionMethod($command, 'createTagDirectory');
    $method->setAccessible(true);

    $testCases = [
        ['tags' => ['café']] => 'Café',
        ['tags' => ['user_プロファイル']] => 'UserProファイル',
        ['tags' => ['api-测试']] => 'Api測試',
    ];

    foreach ($testCases as $input => $expected) {
        $result = $method->invoke($command, $input);
        expect($result)->toBe($expected, 'Failed for input: '.json_encode($input));
    }
});

test('tool and resource creation in same tag directory works correctly', function () {
    // Simulate swagger generating both tool and resource with same tag
    $toolCommand = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpToolCommand;
    $resourceCommand = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpResourceCommand;

    // Set up both commands with same tag directory
    $toolProperty = new ReflectionProperty($toolCommand, 'dynamicParams');
    $toolProperty->setAccessible(true);
    $toolProperty->setValue($toolCommand, ['tagDirectory' => 'Pet']);

    $resourceProperty = new ReflectionProperty($resourceCommand, 'dynamicParams');
    $resourceProperty->setAccessible(true);
    $resourceProperty->setValue($resourceCommand, ['tagDirectory' => 'Pet']);

    // Test path generation
    $toolMethod = new ReflectionMethod($toolCommand, 'getPath');
    $toolMethod->setAccessible(true);
    $toolPath = $toolMethod->invoke($toolCommand, 'PetTool');

    $resourceMethod = new ReflectionMethod($resourceCommand, 'getPath');
    $resourceMethod->setAccessible(true);
    $resourcePath = $resourceMethod->invoke($resourceCommand, 'PetResource');

    expect($toolPath)->toBe(app_path('MCP/Tools/Pet/PetTool.php'));
    expect($resourcePath)->toBe(app_path('MCP/Resources/Pet/PetResource.php'));

    // Verify different base directories
    expect(dirname(dirname($toolPath)))->toBe(app_path('MCP/Tools'));
    expect(dirname(dirname($resourcePath)))->toBe(app_path('MCP/Resources'));
});

test('deeply nested tag directories work correctly', function () {
    // Test creating very deep directory structures
    $toolCommand = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpToolCommand;

    $property = new ReflectionProperty($toolCommand, 'dynamicParams');
    $property->setAccessible(true);
    $property->setValue($toolCommand, ['tagDirectory' => 'VeryLongTagNameWithManyWords']);

    $method = new ReflectionMethod($toolCommand, 'getPath');
    $method->setAccessible(true);

    $result = $method->invoke($toolCommand, 'TestTool');
    $expected = app_path('MCP/Tools/VeryLongTagNameWithManyWords/TestTool.php');

    expect($result)->toBe($expected);

    // Test directory creation
    $makeDirectoryMethod = new ReflectionMethod($toolCommand, 'makeDirectory');
    $makeDirectoryMethod->setAccessible(true);

    $directoryResult = $makeDirectoryMethod->invoke($toolCommand, $expected);
    expect(File::isDirectory($directoryResult))->toBeTrue();
});

test('namespace collision prevention with different tags', function () {
    // Test that tools with same name but different tags get different namespaces
    $toolCommand1 = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpToolCommand;
    $toolCommand2 = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpToolCommand;

    // Set up different tag directories
    $property1 = new ReflectionProperty($toolCommand1, 'dynamicParams');
    $property1->setAccessible(true);
    $property1->setValue($toolCommand1, ['tagDirectory' => 'Pet']);

    $property2 = new ReflectionProperty($toolCommand2, 'dynamicParams');
    $property2->setAccessible(true);
    $property2->setValue($toolCommand2, ['tagDirectory' => 'Store']);

    $method = new ReflectionMethod($toolCommand1, 'replaceStubPlaceholders');
    $method->setAccessible(true);

    $stub = 'namespace {{ namespace }}; class {{ className }} { }';
    $result1 = $method->invoke($toolCommand1, $stub, 'UpdateTool', 'update');
    $result2 = $method->invoke($toolCommand2, $stub, 'UpdateTool', 'update');

    expect($result1)->toContain('namespace App\\MCP\\Tools\\Pet;');
    expect($result2)->toContain('namespace App\\MCP\\Tools\\Store;');

    // Both contain same class name but different namespaces
    expect($result1)->toContain('class UpdateTool');
    expect($result2)->toContain('class UpdateTool');
});

test('swagger generation with mixed tagged and untagged endpoints', function () {
    // Create swagger with mix of tagged and untagged endpoints
    $swaggerData = [
        'openapi' => '3.0.0',
        'info' => [
            'title' => 'Mixed API',
            'version' => '1.0.0',
        ],
        'paths' => [
            '/pet' => [
                'post' => [
                    'tags' => ['pet'],
                    'operationId' => 'addPet',
                    'summary' => 'Add pet',
                    'responses' => ['200' => ['description' => 'Success']],
                ],
            ],
            '/health' => [
                'get' => [
                    // No tags
                    'operationId' => 'healthCheck',
                    'summary' => 'Health check',
                    'responses' => ['200' => ['description' => 'Success']],
                ],
            ],
            '/store/inventory' => [
                'get' => [
                    'tags' => ['store', 'inventory'], // Multiple tags
                    'operationId' => 'getInventory',
                    'summary' => 'Get inventory',
                    'responses' => ['200' => ['description' => 'Success']],
                ],
            ],
        ],
    ];

    $swaggerPath = storage_path('swagger-mixed-test.json');
    File::put($swaggerPath, json_encode($swaggerData));

    try {
        $this->artisan('make:swagger-mcp-tools', [
            'swagger_file' => $swaggerPath,
            '--force' => true,
        ])
            ->assertExitCode(0);

        // Check tagged endpoint goes to Pet directory
        expect(File::exists(app_path('MCP/Tools/Pet/AddPetTool.php')))->toBeTrue();

        // Check untagged endpoint goes to General directory
        expect(File::exists(app_path('MCP/Tools/General/HealthCheckTool.php')))->toBeTrue();

        // Check multi-tagged endpoint uses first tag (Store)
        expect(File::exists(app_path('MCP/Tools/Store/GetInventoryTool.php')))->toBeTrue();

        // Verify namespaces are correct
        $petContent = File::get(app_path('MCP/Tools/Pet/AddPetTool.php'));
        expect($petContent)->toContain('namespace App\\MCP\\Tools\\Pet;');

        $healthContent = File::get(app_path('MCP/Tools/General/HealthCheckTool.php'));
        expect($healthContent)->toContain('namespace App\\MCP\\Tools\\General;');

        $storeContent = File::get(app_path('MCP/Tools/Store/GetInventoryTool.php'));
        expect($storeContent)->toContain('namespace App\\MCP\\Tools\\Store;');

    } finally {
        if (File::exists($swaggerPath)) {
            File::delete($swaggerPath);
        }
    }
});
