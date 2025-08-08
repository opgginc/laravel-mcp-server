<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Create a minimal config file for testing
    $configDir = config_path();
    if (! File::isDirectory($configDir)) {
        File::makeDirectory($configDir, 0755, true);
    }

    $configContent = "<?php\n\nreturn [\n    'tools' => [],\n    'resources' => [],\n];";
    File::put(config_path('mcp-server.php'), $configContent);
});

afterEach(function () {
    File::deleteDirectory(app_path('MCP/Resources'));
    if (File::exists(config_path('mcp-server.php'))) {
        File::delete(config_path('mcp-server.php'));
    }
});

test('make:mcp-resource generates a resource class', function () {
    $path = app_path('MCP/Resources/TestResource.php');

    $this->artisan('make:mcp-resource', ['name' => 'Test'])
        ->expectsOutputToContain('Created')
        ->assertExitCode(0);

    expect(File::exists($path))->toBeTrue();
});

test('getPath returns correct path without tag directory', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpResourceCommand;

    $method = new ReflectionMethod($command, 'getPath');
    $method->setAccessible(true);

    $result = $method->invoke($command, 'TestResource');
    $expected = app_path('MCP/Resources/TestResource.php');

    expect($result)->toBe($expected);
});

test('getPath returns correct path with tag directory', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpResourceCommand;

    // Set dynamicParams using reflection
    $property = new ReflectionProperty($command, 'dynamicParams');
    $property->setAccessible(true);
    $property->setValue($command, ['tagDirectory' => 'Pet']);

    $method = new ReflectionMethod($command, 'getPath');
    $method->setAccessible(true);

    $result = $method->invoke($command, 'PetResource');
    $expected = app_path('MCP/Resources/Pet/PetResource.php');

    expect($result)->toBe($expected);
});

test('replaceStubPlaceholders generates correct namespace without tag directory', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpResourceCommand;

    $method = new ReflectionMethod($command, 'replaceStubPlaceholders');
    $method->setAccessible(true);

    $stub = 'namespace {{ namespace }}; class {{ className }} { }';
    $result = $method->invoke($command, $stub, 'TestResource');

    expect($result)->toContain('namespace App\\MCP\\Resources;');
    expect($result)->toContain('class TestResource');
});

test('replaceStubPlaceholders generates correct namespace with tag directory', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpResourceCommand;

    // Set dynamicParams using reflection
    $property = new ReflectionProperty($command, 'dynamicParams');
    $property->setAccessible(true);
    $property->setValue($command, ['tagDirectory' => 'Pet']);

    $method = new ReflectionMethod($command, 'replaceStubPlaceholders');
    $method->setAccessible(true);

    $stub = 'namespace {{ namespace }}; class {{ className }} { }';
    $result = $method->invoke($command, $stub, 'PetResource');

    expect($result)->toContain('namespace App\\MCP\\Resources\\Pet;');
    expect($result)->toContain('class PetResource');
});

test('makeDirectory creates nested directories for resources', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpResourceCommand;

    $method = new ReflectionMethod($command, 'makeDirectory');
    $method->setAccessible(true);

    $testPath = app_path('MCP/Resources/Pet/Store/TestResource.php');
    $result = $method->invoke($command, $testPath);

    $expectedDirectory = dirname($testPath);
    expect($result)->toBe($expectedDirectory);
    expect(File::isDirectory($expectedDirectory))->toBeTrue();
});
