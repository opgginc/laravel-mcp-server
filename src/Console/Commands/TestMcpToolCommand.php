<?php

namespace OPGG\LaravelMcpServer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;
use Symfony\Component\Console\Helper\Table;

class TestMcpToolCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:test-tool {tool? : The name or class of the tool to test} {--input= : JSON input for the tool} {--list : List all available tools}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test an MCP tool with simulated inputs';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // List all tools if --list option is provided
        if ($this->option('list')) {
            return $this->listAllTools();
        }

        // Get the tool name from argument or prompt for it
        $toolIdentifier = $this->argument('tool');
        if (!$toolIdentifier) {
            $toolIdentifier = $this->askForTool();
            if (!$toolIdentifier) {
                return 1;
            }
        }

        // Find the tool class
        $toolClass = $this->findToolClass($toolIdentifier);
        if (!$toolClass) {
            $this->error("Tool '{$toolIdentifier}' not found.");
            return 1;
        }

        // Create tool instance
        $tool = App::make($toolClass);
        if (!($tool instanceof ToolInterface)) {
            $this->error("The class '{$toolClass}' does not implement ToolInterface.");
            return 1;
        }

        $this->info("Testing tool: {$tool->getName()} ({$toolClass})");
        $this->line("Description: {$tool->getDescription()}");
        $this->newLine();

        // Get input schema
        $inputSchema = $tool->getInputSchema();
        $this->line("Input schema:");
        $this->displaySchema($inputSchema);
        $this->newLine();

        // Get input data
        $inputData = $this->getInputData($inputSchema);
        if ($inputData === null) {
            return 1;
        }

        // Execute the tool
        $this->info("Executing tool with input data:");
        $this->line(json_encode($inputData, JSON_PRETTY_PRINT));
        $this->newLine();

        try {
            $this->info("Tool execution result:");
            $result = $tool->execute($inputData);
            
            if (is_array($result) || is_object($result)) {
                $this->line(json_encode($result, JSON_PRETTY_PRINT));
            } else {
                $this->line((string) $result);
            }
            
            $this->info("Tool executed successfully!");
            return 0;
        } catch (\Throwable $e) {
            $this->error("Error executing tool: {$e->getMessage()}");
            $this->line("Stack trace:");
            $this->line($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Find the tool class from a given identifier.
     *
     * @param string $identifier Tool name or class
     * @return string|null Full class name or null if not found
     */
    protected function findToolClass(string $identifier): ?string
    {
        // First check if the identifier is a direct class name
        if (class_exists($identifier) && $this->isToolClass($identifier)) {
            return $identifier;
        }

        // Load all registered tools from config
        $configuredTools = Config::get('mcp-server.tools', []);
        
        // Check for exact class match
        foreach ($configuredTools as $toolClass) {
            if (Str::endsWith($toolClass, "\\{$identifier}") || $toolClass === $identifier) {
                return $toolClass;
            }
        }

        // Check for tool name match (case insensitive)
        foreach ($configuredTools as $toolClass) {
            if (class_exists($toolClass)) {
                $instance = App::make($toolClass);
                if ($instance instanceof ToolInterface && 
                    strtolower($instance->getName()) === strtolower($identifier)) {
                    return $toolClass;
                }
            }
        }

        return null;
    }

    /**
     * Check if a class implements ToolInterface.
     *
     * @param string $class
     * @return bool
     */
    protected function isToolClass(string $class): bool
    {
        try {
            return is_subclass_of($class, ToolInterface::class);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Display JSON schema in a readable format.
     *
     * @param array $schema
     * @param string $indent
     */
    protected function displaySchema(array $schema, string $indent = ''): void
    {
        if (isset($schema['properties']) && is_array($schema['properties'])) {
            foreach ($schema['properties'] as $propName => $propSchema) {
                $type = $propSchema['type'] ?? 'any';
                $description = $propSchema['description'] ?? '';
                $required = in_array($propName, $schema['required'] ?? []) ? '(required)' : '(optional)';
                
                $this->line("{$indent}- {$propName}: {$type} {$required}");
                if ($description) {
                    $this->line("{$indent}  Description: {$description}");
                }
                
                // If this is an object with nested properties
                if ($type === 'object' && isset($propSchema['properties'])) {
                    $this->line("{$indent}  Properties:");
                    $this->displaySchema($propSchema, $indent . '    ');
                }
                
                // If this is an array with items
                if ($type === 'array' && isset($propSchema['items'])) {
                    $itemType = $propSchema['items']['type'] ?? 'any';
                    $this->line("{$indent}  Items: {$itemType}");
                    if (isset($propSchema['items']['properties'])) {
                        $this->line("{$indent}  Item Properties:");
                        $this->displaySchema($propSchema['items'], $indent . '    ');
                    }
                }
            }
        }
    }

    /**
     * Get input data from user or from the provided option.
     *
     * @param array $schema
     * @return array|null
     */
    protected function getInputData(array $schema): ?array
    {
        // If input is provided as an option, use that
        $inputOption = $this->option('input');
        if ($inputOption) {
            try {
                $decodedInput = json_decode($inputOption, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception(json_last_error_msg());
                }
                return $decodedInput;
            } catch (\Throwable $e) {
                $this->error("Invalid JSON input: {$e->getMessage()}");
                return null;
            }
        }

        // Otherwise, interactively build the input
        $input = [];
        
        if (!isset($schema['properties']) || !is_array($schema['properties'])) {
            return $input;
        }

        foreach ($schema['properties'] as $propName => $propSchema) {
            $type = $propSchema['type'] ?? 'string';
            $description = $propSchema['description'] ?? '';
            $required = in_array($propName, $schema['required'] ?? []);
            
            $this->line("Property: {$propName} ({$type})");
            if ($description) {
                $this->line("Description: {$description}");
            }
            
            if ($type === 'object') {
                $this->line("Enter JSON for object (or leave empty to skip):");
                $jsonInput = $this->ask('JSON');
                if (!empty($jsonInput)) {
                    try {
                        $input[$propName] = json_decode($jsonInput, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            throw new \Exception(json_last_error_msg());
                        }
                    } catch (\Throwable $e) {
                        $this->error("Invalid JSON: {$e->getMessage()}");
                        $input[$propName] = null;
                    }
                } else if ($required) {
                    $this->warn("Required field skipped. Using empty object.");
                    $input[$propName] = [];
                }
            } else if ($type === 'array') {
                $this->line("Enter JSON for array (or leave empty to skip):");
                $jsonInput = $this->ask('JSON');
                if (!empty($jsonInput)) {
                    try {
                        $input[$propName] = json_decode($jsonInput, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            throw new \Exception(json_last_error_msg());
                        }
                        if (!is_array($input[$propName])) {
                            throw new \Exception("Not an array");
                        }
                    } catch (\Throwable $e) {
                        $this->error("Invalid JSON array: {$e->getMessage()}");
                        $input[$propName] = [];
                    }
                } else if ($required) {
                    $this->warn("Required field skipped. Using empty array.");
                    $input[$propName] = [];
                }
            } else if ($type === 'boolean') {
                $default = $propSchema['default'] ?? false;
                $input[$propName] = $this->confirm("Value (yes/no)", $default);
            } else if ($type === 'number' || $type === 'integer') {
                $default = $propSchema['default'] ?? '';
                $value = $this->ask("Value" . ($default !== '' ? " (default: {$default})" : ''));
                if ($value === '' && $default !== '') {
                    $input[$propName] = $default;
                } else if ($value === '' && $required) {
                    $this->warn("Required field skipped. Using 0.");
                    $input[$propName] = 0;
                } else if ($value !== '') {
                    $input[$propName] = ($type === 'integer') ? (int)$value : (float)$value;
                }
            } else {
                // String or other types
                $default = $propSchema['default'] ?? '';
                $value = $this->ask("Value" . ($default !== '' ? " (default: {$default})" : ''));
                if ($value === '' && $default !== '') {
                    $input[$propName] = $default;
                } else if ($value === '' && $required) {
                    $this->warn("Required field skipped. Using empty string.");
                    $input[$propName] = '';
                } else if ($value !== '') {
                    $input[$propName] = $value;
                }
            }
            
            $this->newLine();
        }

        return $input;
    }

    /**
     * List all available tools.
     *
     * @return int
     */
    protected function listAllTools(): int
    {
        $configuredTools = Config::get('mcp-server.tools', []);
        
        if (empty($configuredTools)) {
            $this->warn("No MCP tools are configured. Add tools in config/mcp-server.php");
            return 0;
        }

        $tools = [];
        
        foreach ($configuredTools as $toolClass) {
            try {
                if (class_exists($toolClass)) {
                    $instance = App::make($toolClass);
                    if ($instance instanceof ToolInterface) {
                        $tools[] = [
                            'name' => $instance->getName(),
                            'class' => $toolClass,
                            'description' => Str::limit($instance->getDescription(), 50)
                        ];
                    }
                }
            } catch (\Throwable $e) {
                $this->warn("Couldn't load tool class: {$toolClass}");
            }
        }

        $this->info("Available MCP Tools:");
        $this->table(['Name', 'Class', 'Description'], $tools);
        
        $this->line("\nTo test a specific tool, run:");
        $this->line("  php artisan mcp:test-tool [tool_name]");
        $this->line("  php artisan mcp:test-tool --input='{\"param\":\"value\"}'");
        
        return 0;
    }

    /**
     * Ask the user to select a tool.
     *
     * @return string|null
     */
    protected function askForTool(): ?string
    {
        $configuredTools = Config::get('mcp-server.tools', []);
        
        if (empty($configuredTools)) {
            $this->warn("No MCP tools are configured. Add tools in config/mcp-server.php");
            return null;
        }

        $choices = [];
        $validTools = [];
        
        foreach ($configuredTools as $toolClass) {
            try {
                if (class_exists($toolClass)) {
                    $instance = App::make($toolClass);
                    if ($instance instanceof ToolInterface) {
                        $name = $instance->getName();
                        $choices[] = "{$name} ({$toolClass})";
                        $validTools[] = $toolClass;
                    }
                }
            } catch (\Throwable $e) {
                // Skip tools that can't be loaded
            }
        }

        if (empty($choices)) {
            $this->warn("No valid MCP tools found.");
            return null;
        }

        $selectedIndex = array_search(
            $this->choice('Select a tool to test', $choices),
            $choices
        );

        return $validTools[$selectedIndex] ?? null;
    }
}
