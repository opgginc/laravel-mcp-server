<?php

namespace OPGG\LaravelMcpServer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

class MigrateToolsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:migrate-tools {path? : The path to your MCP tools directory}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates older MCP tools to the current ToolInterface structure (supports v1.0.x → v1.3.0 and v1.1.x/v1.2.x → v1.3.0).';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $toolsPath = $this->argument('path') ?? app_path('MCP/Tools');

        if (! File::isDirectory($toolsPath)) {
            $this->error("The specified path `{$toolsPath}` is not a directory or does not exist.");

            return self::FAILURE;
        }

        $this->info("Starting migration scan for tools in: {$toolsPath}");
        $this->info('This tool supports migration from v1.0.x, v1.1.x, and v1.2.x to v1.3.0');

        $finder = new Finder;
        $finder->files()->in($toolsPath)->name('*.php');

        if (! $finder->hasResults()) {
            $this->info('No PHP files found in the specified path.');

            return self::SUCCESS;
        }

        $potentialCandidates = 0;

        foreach ($finder as $file) {
            $content = $file->getContents();

            // Check for tools that need migration
            $filePath = $file->getRealPath();
            $toolVersion = $this->detectToolVersion($content);
            $needsMigration = $toolVersion !== null && $toolVersion !== '1.3.0';

            if ($needsMigration) {
                $this->line("Found {$toolVersion} tool requiring migration to 1.3.0: {$filePath}");
                $potentialCandidates++;

                $backupFilePath = $filePath.'.backup';

                if (File::exists($backupFilePath)) {
                    $this->warn("Backup for '{$filePath}' already exists at '{$backupFilePath}'. Skipping migration for this file.");

                    continue; // Skip to the next file
                }

                try {
                    if (File::copy($filePath, $backupFilePath)) {
                        $this->info("Backed up '{$filePath}' to '{$backupFilePath}'.");

                        // Proceed with migration
                        $originalContent = File::get($filePath);

                        // Apply migration strategy based on detected version
                        $this->info("Performing migration from {$toolVersion} to 1.3.0...");
                        $modifiedContent = $this->applyMigrationStrategy($toolVersion, $originalContent);

                        if ($modifiedContent !== $originalContent) {
                            if (File::put($filePath, $modifiedContent)) {
                                $this->info("Successfully migrated '{$filePath}'.");
                            } else {
                                $this->error("Failed to write changes to '{$filePath}'. Restoring from backup might be needed.");
                            }
                        } else {
                            $this->info("No changes were necessary for '{$filePath}' during migration content generation (this might indicate an issue or already migrated parts).");
                        }

                    } else {
                        $this->error("Failed to create backup for '{$filePath}'. Skipping migration for this file.");

                        continue;
                    }
                } catch (\Exception $e) {
                    $this->error("Error creating backup or migrating '{$filePath}': ".$e->getMessage().'. Skipping migration for this file.');

                    continue;
                }

            }
        }

        if ($potentialCandidates > 0) {
            $this->info("Scan complete. Processed {$potentialCandidates} potential candidates.");
        } else {
            $this->info('Scan complete. No files seem to require migration based on initial checks.');
        }

        return self::SUCCESS;
    }

    /**
     * Detect the version of a tool based on its content
     */
    private function detectToolVersion(string $content): ?string
    {
        $isToolInterface = str_contains($content, 'implements ToolInterface') ||
                          str_contains($content, 'use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;');

        if (! $isToolInterface) {
            return null; // Not a tool
        }

        // Check for v1.0.x style methods (old method names)
        $hasV1Methods = str_contains($content, 'public function getName(): string') ||
                       str_contains($content, 'public function getDescription(): string') ||
                       str_contains($content, 'public function getInputSchema(): array') ||
                       str_contains($content, 'public function getAnnotations(): array');

        if ($hasV1Methods) {
            return '1.0.x';
        }

        // Check for isStreaming() method (v1.3.0 style)
        $hasIsStreaming = str_contains($content, 'public function isStreaming(): bool');

        if ($hasIsStreaming) {
            return '1.3.0'; // Already migrated
        }

        // Check for messageType() method without isStreaming() (v1.1.x/v1.2.x tools)
        $hasMessageType = str_contains($content, 'public function messageType(): ProcessMessageType');

        if ($hasMessageType) {
            return '1.1.x'; // Could be 1.1.x or 1.2.x, needs isStreaming() method
        }

        return null; // Unknown or not a proper tool
    }

    /**
     * Apply the appropriate migration strategy
     */
    private function applyMigrationStrategy(string $fromVersion, string $content): string
    {
        return match ($fromVersion) {
            '1.0.x' => $this->migrateFromV1_0($content),
            '1.1.x' => $this->migrateFromV1_1($content),
            default => $content,
        };
    }

    /**
     * Migrate v1.0.x tools to v1.3.0 (full migration)
     */
    private function migrateFromV1_0(string $content): string
    {
        $modifiedContent = $content;

        // 1. Add isStreaming() method
        $isStreamingMethod = PHP_EOL.
            '    public function isStreaming(): bool'.PHP_EOL.
            '    {'.PHP_EOL.
            '        return false;'.PHP_EOL.
            '    }'.PHP_EOL;

        // Add it after the class opening brace and ToolInterface implementation
        if (! str_contains($modifiedContent, 'public function isStreaming(): bool')) {
            $modifiedContent = preg_replace(
                '/(implements\s+ToolInterface\s*\{)/',
                '$1'.$isStreamingMethod,
                $modifiedContent,
                1
            );
        }

        // 2. Rename methods
        $replacements = [
            'public function getName(): string' => 'public function name(): string',
            'public function getDescription(): string' => 'public function description(): string',
            'public function getInputSchema(): array' => 'public function inputSchema(): array',
            'public function getAnnotations(): array' => 'public function annotations(): array',
        ];

        foreach ($replacements as $old => $new) {
            $modifiedContent = str_replace($old, $new, $modifiedContent);
        }

        return $modifiedContent;
    }

    /**
     * Migrate v1.1.x/v1.2.x tools to v1.3.0 (add isStreaming method)
     */
    private function migrateFromV1_1(string $content): string
    {
        $modifiedContent = $content;

        // Add isStreaming() method after messageType() method
        $isStreamingMethod = PHP_EOL.PHP_EOL.
            '    public function isStreaming(): bool'.PHP_EOL.
            '    {'.PHP_EOL.
            '        return false;'.PHP_EOL.
            '    }';

        // Find messageType method and add isStreaming after it
        if (preg_match('/(public function messageType\(\): ProcessMessageType\s*\{[^}]*\})/s', $content, $matches)) {
            $messageTypeMethod = $matches[1];
            $replacement = $messageTypeMethod.$isStreamingMethod;
            $modifiedContent = str_replace($messageTypeMethod, $replacement, $modifiedContent);
        } else {
            // If messageType method not found, add isStreaming after class opening
            if (! str_contains($modifiedContent, 'public function isStreaming(): bool')) {
                $modifiedContent = preg_replace(
                    '/(implements\s+ToolInterface\s*\{)/',
                    '$1'.PHP_EOL.'    public function isStreaming(): bool'.PHP_EOL.'    {'.PHP_EOL.'        return false;'.PHP_EOL.'    }'.PHP_EOL,
                    $modifiedContent,
                    1
                );
            }
        }

        return $modifiedContent;
    }
}
