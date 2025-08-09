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
    protected $signature = 'mcp:migrate-tools {path? : The path to your MCP tools directory} {--no-backup : Skip creating backup files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates older MCP tools to v1.3.0: removes messageType() method, adds isStreaming() only for SSE tools. Creates backup files by default (supports v1.0.x → v1.3.0 and v1.1.x/v1.2.x → v1.3.0).';

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
        $createBackups = null;

        foreach ($finder as $file) {
            $content = $file->getContents();

            // Check for tools that need migration
            $filePath = $file->getRealPath();
            $toolVersion = $this->detectToolVersion($content);
            $needsMigration = $toolVersion !== null && $toolVersion !== '1.3.0';

            if ($needsMigration) {
                $this->line("Found {$toolVersion} tool requiring migration to 1.3.0: {$filePath}");
                $potentialCandidates++;

                // Ask about backup creation only once
                if ($createBackups === null) {
                    if ($this->option('no-backup')) {
                        $createBackups = false;
                    } elseif ($this->option('no-interaction')) {
                        $createBackups = true; // Default to yes in no-interaction mode
                    } else {
                        $createBackups = $this->confirm(
                            'Do you want to create backup files before migration? (Recommended)',
                            true // Default to yes
                        );

                        if ($createBackups) {
                            $this->info('Backup files will be created with .backup extension.');
                        } else {
                            $this->warn('No backup files will be created. Migration will modify files directly.');
                        }
                    }
                }

                $backupFilePath = $filePath.'.backup';

                // Check if backup already exists when backups are enabled
                if ($createBackups && File::exists($backupFilePath)) {
                    $this->warn("Backup for '{$filePath}' already exists at '{$backupFilePath}'. Skipping migration for this file.");

                    continue; // Skip to the next file
                }

                try {
                    // Create backup if requested
                    if ($createBackups) {
                        if (File::copy($filePath, $backupFilePath)) {
                            $this->info("Backed up '{$filePath}' to '{$backupFilePath}'.");
                        } else {
                            $this->error("Failed to create backup for '{$filePath}'. Skipping migration for this file.");

                            continue;
                        }
                    }

                    // Proceed with migration
                    $originalContent = File::get($filePath);

                    // Apply migration strategy based on detected version
                    $this->info("Performing migration from {$toolVersion} to 1.3.0...");
                    $modifiedContent = $this->applyMigrationStrategy($toolVersion, $originalContent);

                    if ($modifiedContent !== $originalContent) {
                        if (File::put($filePath, $modifiedContent)) {
                            $this->info("Successfully migrated '{$filePath}'.");
                        } else {
                            $this->error("Failed to write changes to '{$filePath}'.".($createBackups ? ' You can restore from backup if needed.' : ''));
                        }
                    } else {
                        $this->info("No changes were necessary for '{$filePath}' during migration content generation (this might indicate an issue or already migrated parts).");
                    }

                } catch (\Exception $e) {
                    $this->error("Error migrating '{$filePath}': ".$e->getMessage().'. Skipping migration for this file.');

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
     * Migrate v1.0.x tools to v1.3.0 (rename methods only, no isStreaming since v1.0.x defaulted to HTTP)
     */
    private function migrateFromV1_0(string $content): string
    {
        $modifiedContent = $content;

        // Rename methods only - v1.0.x tools defaulted to HTTP so no isStreaming() needed
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
     * Migrate v1.1.x/v1.2.x tools to v1.3.0 (remove messageType and conditionally add isStreaming)
     */
    private function migrateFromV1_1(string $content): string
    {
        $modifiedContent = $content;

        // Find messageType method and determine if it's SSE or HTTP
        if (preg_match('/(public function messageType\(\): ProcessMessageType\s*\{[^}]*\})/s', $content, $matches)) {
            $messageTypeMethod = $matches[1];

            // Check if the messageType returns SSE
            $isSSE = str_contains($messageTypeMethod, 'ProcessMessageType::SSE');

            if ($isSSE) {
                // For SSE tools: Replace messageType with isStreaming() returning true
                $isStreamingMethod = '    public function isStreaming(): bool'.PHP_EOL.
                    '    {'.PHP_EOL.
                    '        return true;'.PHP_EOL.
                    '    }';
                $modifiedContent = str_replace($messageTypeMethod, $isStreamingMethod, $modifiedContent);
            } else {
                // For HTTP tools: Just remove the messageType method completely
                $modifiedContent = str_replace($messageTypeMethod, '', $modifiedContent);

                // Clean up any extra newlines left behind
                $modifiedContent = preg_replace('/\n\s*\n\s*\n/', "\n\n", $modifiedContent);
            }

            // Remove the ProcessMessageType import if it's no longer needed
            if (! $isSSE && ! str_contains($modifiedContent, 'ProcessMessageType::')) {
                $modifiedContent = preg_replace('/use OPGG\\\\LaravelMcpServer\\\\Enums\\\\ProcessMessageType;\s*\n/', '', $modifiedContent);
            }
        }

        return $modifiedContent;
    }
}
