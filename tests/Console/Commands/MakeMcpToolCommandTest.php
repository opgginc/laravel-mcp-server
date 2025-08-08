<?php

use Illuminate\Support\Facades\File;

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

test('make:mcp-tool generates tool in root directory by default', function () {
    $this->artisan('make:mcp-tool', ['name' => 'TestTool'])
        ->expectsOutputToContain('Created')
        ->assertExitCode(0);

    $path = app_path('MCP/Tools/TestTool.php');
    expect(File::exists($path))->toBeTrue();

    // Verify namespace is correct
    $content = File::get($path);
    expect($content)->toContain('namespace App\\MCP\\Tools;');
});

test('getPath returns correct path without tag directory', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpToolCommand();
    
    $method = new ReflectionMethod($command, 'getPath');
    $method->setAccessible(true);
    
    $result = $method->invoke($command, 'TestTool');
    $expected = app_path('MCP/Tools/TestTool.php');
    
    expect($result)->toBe($expected);
});

test('getPath returns correct path with tag directory', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpToolCommand();
    
    // Set dynamicParams using reflection
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
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpToolCommand();
    
    $method = new ReflectionMethod($command, 'replaceStubPlaceholders');
    $method->setAccessible(true);
    
    $stub = 'namespace {{ namespace }}; class {{ className }} { public function name() { return "{{ toolName }}"; } }';
    $result = $method->invoke($command, $stub, 'TestTool', 'test-tool');
    
    expect($result)->toContain('namespace App\\MCP\\Tools;');
    expect($result)->toContain('class TestTool');
    expect($result)->toContain('return "test-tool";');
});

test('replaceStubPlaceholders generates correct namespace with tag directory', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpToolCommand();
    
    // Set dynamicParams using reflection
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
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpToolCommand();
    
    $method = new ReflectionMethod($command, 'makeDirectory');
    $method->setAccessible(true);
    
    $testPath = app_path('MCP/Tools/Pet/Store/TestTool.php');
    $result = $method->invoke($command, $testPath);
    
    $expectedDirectory = dirname($testPath);
    expect($result)->toBe($expectedDirectory);
    expect(File::isDirectory($expectedDirectory))->toBeTrue();
});

test('tool with tag directory is properly registered in config', function () {
    // Create a tool using dynamicParams (simulating swagger generation)
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpToolCommand();
    
    // Set up the command with tag directory
    $property = new ReflectionProperty($command, 'dynamicParams');
    $property->setAccessible(true);
    $property->setValue($command, ['tagDirectory' => 'Pet']);
    
    // Generate the tool manually to test registration
    $className = 'AddPetTool';
    $toolName = 'add-pet';
    
    $path = app_path('MCP/Tools/Pet/AddPetTool.php');
    $directory = dirname($path);
    if (!File::isDirectory($directory)) {
        File::makeDirectory($directory, 0755, true);
    }
    
    // Create a mock tool file
    $toolContent = '<?php

namespace App\\MCP\\Tools\\Pet;

use OPGG\\LaravelMcpServer\\Services\\ToolService\\ToolInterface;

class AddPetTool implements ToolInterface
{
    public function name(): string
    {
        return "add-pet";
    }
    
    public function description(): string
    {
        return "Add a pet";
    }
    
    public function inputSchema(): array
    {
        return [];
    }
    
    public function execute(array $params): array
    {
        return ["result" => "success"];
    }
    
    public function messageType(): string
    {
        return "text";
    }
}';
    
    File::put($path, $toolContent);
    
    // Test that the tool can be registered with the correct fully qualified class name
    $fullyQualifiedClassName = 'App\\MCP\\Tools\\Pet\\AddPetTool';
    
    $method = new ReflectionMethod($command, 'registerToolInConfig');
    $method->setAccessible(true);
    
    $result = $method->invoke($command, $fullyQualifiedClassName);
    expect($result)->toBeTrue();
    
    // Verify the tool was added to config
    $configContent = File::get(config_path('mcp-server.php'));
    expect($configContent)->toContain($fullyQualifiedClassName);
});

test('handles directory creation permissions gracefully', function () {
    $command = new \OPGG\LaravelMcpServer\Console\Commands\MakeMcpToolCommand();
    
    $method = new ReflectionMethod($command, 'makeDirectory');
    $method->setAccessible(true);
    
    // Test with a valid path - should not throw exception
    $validPath = app_path('MCP/Tools/TestDir/TestTool.php');
    $result = $method->invoke($command, $validPath);
    
    expect($result)->toBe(dirname($validPath));
    expect(File::isDirectory(dirname($validPath)))->toBeTrue();
});