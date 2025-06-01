<?php

namespace OPGG\LaravelMcpServer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File; // Will be needed later for file operations
use Symfony\Component\Finder\Finder; // Will be needed later for finding files

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
    protected $description = 'Migrates older MCP tools to the v1.1.0 ToolInterface structure.';

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

            return Command::FAILURE;
        }

        $this->info("Starting migration scan for tools in: {$toolsPath}");

        $finder = new Finder;
        $finder->files()->in($toolsPath)->name('*.php');

        if (! $finder->hasResults()) {
            $this->info('No PHP files found in the specified path.');

            return Command::SUCCESS;
        }

        $potentialCandidates = 0;

        foreach ($finder as $file) {
            $content = $file->getContents();

            // Basic check for old ToolInterface and old method names
            // This is a heuristic and might need refinement.
            $isOldToolInterface = str_contains($content, 'implements ToolInterface') || str_contains($content, 'use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;');

            $hasOldMethods = str_contains($content, 'public function getName(): string') ||
                             str_contains($content, 'public function getDescription(): string') ||
                             str_contains($content, 'public function getInputSchema(): array') ||
                             str_contains($content, 'public function getAnnotations(): array');

            $filePath = $file->getRealPath();

            $isOldToolInterface = str_contains($content, 'implements ToolInterface') || str_contains($content, 'use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;');

            $hasOldMethods = str_contains($content, 'public function getName(): string') ||
                             str_contains($content, 'public function getDescription(): string') ||
                             str_contains($content, 'public function getInputSchema(): array') ||
                             str_contains($content, 'public function getAnnotations(): array');

            if ($isOldToolInterface && $hasOldMethods) {
                $this->line('Found potential candidate for migration: '.$filePath);
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
                        $modifiedContent = $originalContent;

                        // 1. Add 'use ProcessMessageType' if not present
                        $useStatement = 'use OPGG\LaravelMcpServer\Enums\ProcessMessageType;';
                        if (! str_contains($modifiedContent, $useStatement) && ! str_contains($modifiedContent, 'use ProcessMessageType;')) { // Avoid duplicate if aliased
                            // Find the last use statement and add after it
                            if (preg_match_all('/^use\s+[^;]+;$/m', $modifiedContent, $matches, PREG_OFFSET_CAPTURE)) {
                                // Get the last use statement
                                $lastUseMatch = end($matches[0]);
                                $lastUseEndPos = $lastUseMatch[1] + strlen($lastUseMatch[0]);
                                
                                // Insert the new use statement after the last one
                                $modifiedContent = substr_replace(
                                    $modifiedContent,
                                    PHP_EOL . $useStatement,
                                    $lastUseEndPos,
                                    0
                                );
                            } else {
                                // Fallback: Add after namespace or <?php if no existing use statements
                                $modifiedContent = preg_replace(
                                    '/(namespace\s+[^;]+;)/',
                                    '$1' . PHP_EOL . PHP_EOL . $useStatement,
                                    $modifiedContent,
                                    1 // Only replace once
                                );
                            }
                        }

                        // 2. Add messageType() method
                        $messageTypeMethod = PHP_EOL.
                            '    public function messageType(): ProcessMessageType'.PHP_EOL.
                            '    {'.PHP_EOL.
                            '        return ProcessMessageType::SSE;'.PHP_EOL. // Defaulting to SSE as per original thought, can be changed
                            '    }'.PHP_EOL;

                        // Add it after the class opening brace and ToolInterface implementation
                        // Ensure it's not added if already present (simple check)
                        if (! str_contains($modifiedContent, 'public function messageType(): ProcessMessageType')) {
                            $modifiedContent = preg_replace(
                                '/(implements\s+ToolInterface\s*\{)/',
                                '$1'.$messageTypeMethod,
                                $modifiedContent,
                                1 // Only replace once
                            );
                        }

                        // 3. Rename methods
                        $replacements = [
                            'public function getName(): string' => 'public function name(): string',
                            'public function getDescription(): string' => 'public function description(): string',
                            'public function getInputSchema(): array' => 'public function inputSchema(): array',
                            'public function getAnnotations(): array' => 'public function annotations(): array',
                        ];

                        foreach ($replacements as $old => $new) {
                            $modifiedContent = str_replace($old, $new, $modifiedContent);
                        }

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
                        // This case might be rare if File::copy throws an exception on failure,
                        // but good to have a fallback.
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

        return Command::SUCCESS;
    }
}
