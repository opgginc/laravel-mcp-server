<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

afterEach(function () {
    // Clean up any mock directories or files after tests
    $mockBasePath = app_path('MCP/ToolsTest');
    if (File::exists($mockBasePath)) {
        File::deleteDirectory($mockBasePath);
    }
});

function setUpMockToolFile(string $fileName, string $content, string $baseDir = 'MCP/ToolsTest'): string
{
    $path = app_path(Str::finish($baseDir, '/').$fileName);
    File::ensureDirectoryExists(dirname($path));
    File::put($path, $content);

    return $path;
}

function getOldToolContent(string $className = 'OldTool'): string
{
    return <<<PHP
<?php

namespace App\MCP\Tools;

use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;
// No ProcessMessageType import

class {$className} implements ToolInterface
{
    public function getName(): string
    {
        return 'old_tool';
    }

    public function getDescription(): string
    {
        return 'An old tool.';
    }

    public function getInputSchema(): array
    {
        return [];
    }

    public function getAnnotations(): array
    {
        return [];
    }

    public function execute(array \$arguments): mixed
    {
        return 'executed old tool';
    }
}
PHP;
}

function getExpectedNewToolContent(string $className = 'OldTool'): string
{
    // Note: The actual output might have slightly different EOL character handling
    // depending on the preg_replace and str_replace logic. This is an ideal state.
    return <<<PHP
<?php

namespace App\MCP\Tools;

use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;
// No ProcessMessageType import

class {$className} implements ToolInterface
{
    public function isStreaming(): bool
    {
        return false;
    }

    public function name(): string
    {
        return 'old_tool';
    }

    public function description(): string
    {
        return 'An old tool.';
    }

    public function inputSchema(): array
    {
        return [];
    }

    public function annotations(): array
    {
        return [];
    }

    public function execute(array \$arguments): mixed
    {
        return 'executed old tool';
    }
}
PHP;
}

test('command migrates old tool successfully', function () {
    $toolPath = setUpMockToolFile('MyOldTool.php', getOldToolContent('MyOldTool'));
    $backupPath = $toolPath.'.backup';
    $toolDir = dirname($toolPath);

    $this->artisan('mcp:migrate-tools', ['path' => $toolDir])
        ->expectsOutput("Starting migration scan for tools in: {$toolDir}")
        ->expectsOutput('This tool supports migration from v1.0.x, v1.1.x, and v1.2.x to v1.3.0')
        ->expectsOutput("Found 1.0.x tool requiring migration to 1.3.0: {$toolPath}")
        ->expectsOutput("Backed up '{$toolPath}' to '{$backupPath}'.")
        ->expectsOutput('Performing migration from 1.0.x to 1.3.0...')
        ->expectsOutput("Successfully migrated '{$toolPath}'.")
        ->expectsOutput('Scan complete. Processed 1 potential candidates.')
        ->assertExitCode(0);

    expect(File::exists($backupPath))->toBeTrue();
    expect(File::get($backupPath))->toBe(getOldToolContent('MyOldTool'));

    // Normalize whitespace and line endings for comparison
    $expectedContent = trim(preg_replace('/\R/', "\n", getExpectedNewToolContent('MyOldTool')));
    $actualContent = trim(preg_replace('/\R/', "\n", File::get($toolPath)));
    expect($actualContent)->toBe($expectedContent);
});

test('command skips if backup exists', function () {
    $toolPath = setUpMockToolFile('MySkippedTool.php', getOldToolContent('MySkippedTool'));
    $backupPath = $toolPath.'.backup';
    File::copy($toolPath, $backupPath); // Create backup beforehand

    $this->artisan('mcp:migrate-tools', ['path' => dirname($toolPath)])
        ->expectsOutput("Found 1.0.x tool requiring migration to 1.3.0: {$toolPath}")
        ->expectsOutput("Backup for '{$toolPath}' already exists at '{$backupPath}'. Skipping migration for this file.")
        ->assertExitCode(0);

    expect(File::get($toolPath))->toBe(getOldToolContent('MySkippedTool')); // Original should be untouched
});

test('command skips non tool php file', function () {
    $nonToolContent = '<?php namespace App; class NotATool {}';
    $nonToolPath = setUpMockToolFile('NotATool.php', $nonToolContent);

    $this->artisan('mcp:migrate-tools', ['path' => dirname($nonToolPath)])
        ->expectsOutput('Scan complete. No files seem to require migration based on initial checks.')
        ->assertExitCode(0);

    expect(File::exists($nonToolPath.'.backup'))->toBeFalse();
});

function getV1_2ToolContent(string $className = 'V12Tool'): string
{
    return <<<PHP
<?php

namespace App\MCP\Tools;

use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType;

class {$className} implements ToolInterface
{
    public function messageType(): ProcessMessageType
    {
        return ProcessMessageType::HTTP;
    }

    public function name(): string
    {
        return 'v12_tool';
    }

    public function description(): string
    {
        return 'A v1.2 tool.';
    }

    public function inputSchema(): array
    {
        return [];
    }

    public function annotations(): array
    {
        return [];
    }

    public function execute(array \$arguments): mixed
    {
        return 'executed v12 tool';
    }
}
PHP;
}

test('command migrates v1.2 tool to v1.3 successfully', function () {
    $toolPath = setUpMockToolFile('MyV12Tool.php', getV1_2ToolContent('MyV12Tool'));
    $backupPath = $toolPath.'.backup';
    $toolDir = dirname($toolPath);

    $this->artisan('mcp:migrate-tools', ['path' => $toolDir])
        ->expectsOutput("Starting migration scan for tools in: {$toolDir}")
        ->expectsOutput('This tool supports migration from v1.0.x, v1.1.x, and v1.2.x to v1.3.0')
        ->expectsOutput("Found 1.1.x tool requiring migration to 1.3.0: {$toolPath}")
        ->expectsOutput("Backed up '{$toolPath}' to '{$backupPath}'.")
        ->expectsOutput('Performing migration from 1.1.x to 1.3.0...')
        ->expectsOutput("Successfully migrated '{$toolPath}'.")
        ->expectsOutput('Scan complete. Processed 1 potential candidates.')
        ->assertExitCode(0);

    expect(File::exists($backupPath))->toBeTrue();
    expect(File::get($backupPath))->toBe(getV1_2ToolContent('MyV12Tool'));

    // Check that isStreaming method was added
    $migratedContent = File::get($toolPath);
    expect($migratedContent)->toContain('public function isStreaming(): bool');
});

function getV1_1ToolContent(string $className = 'V11Tool'): string
{
    return <<<PHP
<?php

namespace App\MCP\Tools;

use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType;

class {$className} implements ToolInterface
{
    public function messageType(): ProcessMessageType
    {
        return ProcessMessageType::SSE;
    }

    public function name(): string
    {
        return 'v11_tool';
    }

    public function description(): string
    {
        return 'A v1.1 tool.';
    }

    public function inputSchema(): array
    {
        return [];
    }

    public function annotations(): array
    {
        return [];
    }

    public function execute(array \$arguments): mixed
    {
        return 'executed v11 tool';
    }
}
PHP;
}

test('command migrates v1.1 tool to v1.3 successfully', function () {
    $toolPath = setUpMockToolFile('MyV11Tool.php', getV1_1ToolContent('MyV11Tool'));
    $backupPath = $toolPath.'.backup';
    $toolDir = dirname($toolPath);

    $this->artisan('mcp:migrate-tools', ['path' => $toolDir])
        ->expectsOutput("Starting migration scan for tools in: {$toolDir}")
        ->expectsOutput('This tool supports migration from v1.0.x, v1.1.x, and v1.2.x to v1.3.0')
        ->expectsOutput("Found 1.1.x tool requiring migration to 1.3.0: $toolPath")
        ->expectsOutput("Backed up '$toolPath' to '$backupPath'.")
        ->expectsOutput('Performing migration from 1.1.x to 1.3.0...')
        ->expectsOutput("Successfully migrated '$toolPath'.")
        ->expectsOutput('Scan complete. Processed 1 potential candidates.')
        ->assertExitCode(0);

    expect(File::exists($backupPath))->toBeTrue();
    expect(File::get($backupPath))->toBe(getV1_1ToolContent('MyV11Tool'));

    // Check that isStreaming method was added
    $migratedContent = File::get($toolPath);
    expect($migratedContent)->toContain('public function isStreaming(): bool');
    // Original messageType method should still be there for backward compatibility
    expect($migratedContent)->toContain('return ProcessMessageType::SSE');
});

test('command handles invalid path', function () {
    $invalidPath = app_path('MCP/NonExistentPath');
    $this->artisan('mcp:migrate-tools', ['path' => $invalidPath])
        ->expectsOutput("The specified path `$invalidPath` is not a directory or does not exist.")
        ->assertExitCode(1); // Command::FAILURE
});

test('command handles no php files in path', function () {
    $emptyDir = app_path('MCP/ToolsTest/Empty');
    File::ensureDirectoryExists($emptyDir);

    $this->artisan('mcp:migrate-tools', ['path' => $emptyDir])
        ->expectsOutput('No PHP files found in the specified path.')
        ->assertExitCode(0);
});
