<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    File::deleteDirectory(app_path('MCP/Tools'));
});

afterEach(function () {
    File::deleteDirectory(app_path('MCP/Tools'));
});

test('make:mcp-tool generates tool in root directory by default', function () {
    $this->artisan('make:mcp-tool', ['name' => 'TestTool', '--no-interaction' => true])
        ->expectsOutputToContain('Created')
        ->assertExitCode(0);

    $path = app_path('MCP/Tools/TestTool.php');
    expect(File::exists($path))->toBeTrue();

    $content = File::get($path);
    expect($content)->toContain('namespace App\\MCP\\Tools;');
});

test('getPath returns correct path without tag directory', function () {
    $filesystem = new \Illuminate\Filesystem\Filesystem;
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpToolCommand($filesystem);

    $method = new ReflectionMethod($command, 'getPath');
    $method->setAccessible(true);

    $result = $method->invoke($command, 'TestTool');
    $expected = app_path('MCP/Tools/TestTool.php');

    expect($result)->toBe($expected);
});

test('getPath returns correct path with tag directory', function () {
    $filesystem = new \Illuminate\Filesystem\Filesystem;
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpToolCommand($filesystem);

    $property = new ReflectionProperty($command, 'dynamicParams');
    $property->setAccessible(true);
    $property->setValue($command, ['tagDirectory' => 'Pet']);

    $method = new ReflectionMethod($command, 'getPath');
    $method->setAccessible(true);

    $result = $method->invoke($command, 'TestTool');
    $expected = app_path('MCP/Tools/Pet/TestTool.php');

    expect($result)->toBe($expected);
});

test('replaceStubPlaceholders generates correct namespace without tag directory', function () {
    $filesystem = new \Illuminate\Filesystem\Filesystem;
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpToolCommand($filesystem);

    $method = new ReflectionMethod($command, 'replaceStubPlaceholders');
    $method->setAccessible(true);

    $stub = 'namespace {{ namespace }}; class {{ className }} { public function name() { return "{{ toolName }}"; } }';
    $result = $method->invoke($command, $stub, 'TestTool', 'test-tool');

    expect($result)->toContain('namespace App\\MCP\\Tools;');
    expect($result)->toContain('class TestTool');
    expect($result)->toContain('return "test-tool";');
});

test('replaceStubPlaceholders generates correct namespace with tag directory', function () {
    $filesystem = new \Illuminate\Filesystem\Filesystem;
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpToolCommand($filesystem);

    $property = new ReflectionProperty($command, 'dynamicParams');
    $property->setAccessible(true);
    $property->setValue($command, ['tagDirectory' => 'Pet']);

    $method = new ReflectionMethod($command, 'replaceStubPlaceholders');
    $method->setAccessible(true);

    $stub = 'namespace {{ namespace }}; class {{ className }} { public function name() { return "{{ toolName }}"; } }';
    $result = $method->invoke($command, $stub, 'AddPetTool', 'add-pet');

    expect($result)->toContain('namespace App\\MCP\\Tools\\Pet;');
    expect($result)->toContain('class AddPetTool');
    expect($result)->toContain('return "add-pet";');
});

test('makeDirectory creates nested directories', function () {
    $filesystem = new \Illuminate\Filesystem\Filesystem;
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpToolCommand($filesystem);

    $method = new ReflectionMethod($command, 'makeDirectory');
    $method->setAccessible(true);

    $testPath = app_path('MCP/Tools/Pet/Store/TestTool.php');
    $result = $method->invoke($command, $testPath);

    $expectedDirectory = dirname($testPath);
    expect($result)->toBe($expectedDirectory);
    expect(File::isDirectory($expectedDirectory))->toBeTrue();
});

test('programmatic mode works without config files', function () {
    $this->artisan('make:mcp-tool', ['name' => 'ProgrammaticTool', '--programmatic' => true, '--no-interaction' => true])
        ->expectsOutputToContain('Created')
        ->assertExitCode(0);

    expect(File::exists(app_path('MCP/Tools/ProgrammaticTool.php')))->toBeTrue();
});

test('handles directory creation permissions gracefully', function () {
    $filesystem = new \Illuminate\Filesystem\Filesystem;
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpToolCommand($filesystem);

    $method = new ReflectionMethod($command, 'makeDirectory');
    $method->setAccessible(true);

    $validPath = app_path('MCP/Tools/TestDir/TestTool.php');
    $result = $method->invoke($command, $validPath);

    expect($result)->toBe(dirname($validPath));
    expect(File::isDirectory(dirname($validPath)))->toBeTrue();
});
