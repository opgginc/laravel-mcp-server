<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    File::deleteDirectory(app_path('MCP/Resources'));
});

afterEach(function () {
    File::deleteDirectory(app_path('MCP/Resources'));
});

test('make:mcp-resource generates a resource class', function () {
    $path = app_path('MCP/Resources/TestResource.php');

    $this->artisan('make:mcp-resource', ['name' => 'Test', '--no-interaction' => true])
        ->expectsOutputToContain('Created')
        ->assertExitCode(0);

    expect(File::exists($path))->toBeTrue();
});

test('getPath returns correct path without tag directory', function () {
    $filesystem = new \Illuminate\Filesystem\Filesystem;
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpResourceCommand($filesystem);

    $method = new ReflectionMethod($command, 'getPath');
    $method->setAccessible(true);

    $result = $method->invoke($command, 'TestResource');
    $expected = app_path('MCP/Resources/TestResource.php');

    expect($result)->toBe($expected);
});

test('getPath returns correct path with tag directory', function () {
    $filesystem = new \Illuminate\Filesystem\Filesystem;
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpResourceCommand($filesystem);

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
    $filesystem = new \Illuminate\Filesystem\Filesystem;
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpResourceCommand($filesystem);

    $method = new ReflectionMethod($command, 'replaceStubPlaceholders');
    $method->setAccessible(true);

    $stub = 'namespace {{ namespace }}; class {{ className }} { }';
    $result = $method->invoke($command, $stub, 'TestResource');

    expect($result)->toContain('namespace App\\MCP\\Resources;');
    expect($result)->toContain('class TestResource');
});

test('replaceStubPlaceholders generates correct namespace with tag directory', function () {
    $filesystem = new \Illuminate\Filesystem\Filesystem;
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpResourceCommand($filesystem);

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
    $filesystem = new \Illuminate\Filesystem\Filesystem;
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpResourceCommand($filesystem);

    $method = new ReflectionMethod($command, 'makeDirectory');
    $method->setAccessible(true);

    $testPath = app_path('MCP/Resources/Pet/Store/TestResource.php');
    $result = $method->invoke($command, $testPath);

    $expectedDirectory = dirname($testPath);
    expect($result)->toBe($expectedDirectory);
    expect(File::isDirectory($expectedDirectory))->toBeTrue();
});

test('programmatic mode works without config files', function () {
    $this->artisan('make:mcp-resource', ['name' => 'ProgrammaticResource', '--programmatic' => true, '--no-interaction' => true])
        ->expectsOutputToContain('Created')
        ->assertExitCode(0);

    expect(File::exists(app_path('MCP/Resources/ProgrammaticResource.php')))->toBeTrue();
});
