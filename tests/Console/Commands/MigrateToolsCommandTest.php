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
use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
// No ProcessMessageType import

class {$className} implements ToolInterface
{
    public function messageType(): ProcessMessageType
    {
        return ProcessMessageType::SSE;
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

    $this->artisan('mcp:migrate-tools', ['path' => dirname($toolPath)])
        ->expectsOutput('Starting migration scan for tools in: '.dirname($toolPath))
        ->expectsOutput("Found potential candidate for migration: {$toolPath}")
        ->expectsOutput("Backed up '{$toolPath}' to '{$backupPath}'.")
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
        ->expectsOutput("Found potential candidate for migration: {$toolPath}")
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

test('command handles invalid path', function () {
    $invalidPath = app_path('MCP/NonExistentPath');
    $this->artisan('mcp:migrate-tools', ['path' => $invalidPath])
        ->expectsOutput("The specified path `{$invalidPath}` is not a directory or does not exist.")
        ->assertExitCode(1); // Command::FAILURE
});

test('command handles no php files in path', function () {
    $emptyDir = app_path('MCP/ToolsTest/Empty');
    File::ensureDirectoryExists($emptyDir);

    $this->artisan('mcp:migrate-tools', ['path' => $emptyDir])
        ->expectsOutput('No PHP files found in the specified path.')
        ->assertExitCode(0);
});
