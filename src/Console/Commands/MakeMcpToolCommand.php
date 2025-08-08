<?php

namespace OPGG\LaravelMcpServer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeMcpToolCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:mcp-tool {name : The name of the MCP tool} {--programmatic : Use programmatic mode with dynamic parameters}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new MCP tool class';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Dynamic parameters for programmatic generation
     */
    protected array $dynamicParams = [];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $className = $this->getClassName();
        $path = $this->getPath($className);

        // Check if file already exists
        if ($this->files->exists($path)) {
            $this->error("❌ MCP tool {$className} already exists!");

            return 1;
        }

        // Create directories if they don't exist
        $this->makeDirectory($path);

        // Generate the file using stub
        $this->files->put($path, $this->buildClass($className));

        $this->info("✅ Created: {$path}");

        $fullClassName = "\\App\\MCP\\Tools\\{$className}";

        // Ask if they want to automatically register the tool (skip in programmatic mode)
        if (! $this->option('programmatic')) {
            if ($this->confirm('🤖 Would you like to automatically register this tool in config/mcp-server.php?', true)) {
                $this->registerToolInConfig($fullClassName);
            } else {
                $this->info("☑️ Don't forget to register your tool in config/mcp-server.php:");
                $this->comment('    // config/mcp-server.php');
                $this->comment("    'tools' => [");
                $this->comment('        // other tools...');
                $this->comment("        {$fullClassName}::class,");
                $this->comment('    ],');
            }

            // Display testing instructions
            $this->newLine();
            $this->info('You can now test your tool with the following command:');
            $this->comment('    php artisan mcp:test-tool '.$className);
            $this->info('Or view all available tools:');
            $this->comment('    php artisan mcp:test-tool --list');
        } else {
            // In programmatic mode, always register the tool
            $this->registerToolInConfig($fullClassName);
        }

        return 0;
    }

    /**
     * Set dynamic parameters for programmatic generation
     *
     * @return $this
     */
    public function setDynamicParams(array $params): self
    {
        $this->dynamicParams = $params;

        return $this;
    }

    /**
     * Get the class name from the command argument.
     *
     * @return string
     */
    protected function getClassName()
    {
        $name = $this->argument('name');

        // Clean up the input: remove multiple spaces, hyphens, underscores
        // and handle mixed case input
        $name = preg_replace('/[\s\-_]+/', ' ', trim($name));

        // Convert to StudlyCase
        $name = Str::studly($name);

        // Ensure the class name ends with "Tool" if not already
        if (! Str::endsWith($name, 'Tool')) {
            $name .= 'Tool';
        }

        return $name;
    }

    /**
     * Get the destination file path.
     *
     * @return string
     */
    protected function getPath(string $className)
    {
        // Create the file in the app/MCP/Tools directory
        return app_path("MCP/Tools/{$className}.php");
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param  string  $path
     * @return string
     */
    protected function makeDirectory($path)
    {
        $directory = dirname($path);

        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true, true);
        }

        return $directory;
    }

    /**
     * Build the class with the given name.
     *
     * @return string
     */
    protected function buildClass(string $className)
    {
        // Use dynamic stub if in programmatic mode
        if ($this->option('programmatic') && ! empty($this->dynamicParams)) {
            return $this->buildDynamicClass($className);
        }

        $stub = $this->files->get($this->getStubPath());

        // Generate a kebab-case tool name without the 'Tool' suffix
        $toolName = Str::kebab(preg_replace('/Tool$/', '', $className));

        // Ensure tool name doesn't have unwanted characters
        $toolName = preg_replace('/[^a-z0-9\-]/', '', $toolName);

        // Ensure no consecutive hyphens
        $toolName = preg_replace('/\-+/', '-', $toolName);

        // Ensure it starts with a letter
        if (! preg_match('/^[a-z]/', $toolName)) {
            $toolName = 'tool-'.$toolName;
        }

        return $this->replaceStubPlaceholders($stub, $className, $toolName);
    }

    /**
     * Build a class with dynamic parameters
     */
    protected function buildDynamicClass(string $className): string
    {
        // Load the programmatic stub
        $stub = $this->files->get(__DIR__.'/../../stubs/tool.programmatic.stub');

        $params = $this->dynamicParams;

        // Extract parameters
        $toolName = $params['toolName'] ?? Str::kebab(preg_replace('/Tool$/', '', $className));
        $description = $params['description'] ?? 'Auto-generated MCP tool';
        $inputSchema = $params['inputSchema'] ?? [];
        $annotations = $params['annotations'] ?? [];
        $executeLogic = $params['executeLogic'] ?? '        return ["result" => "success"];';
        $imports = $params['imports'] ?? [];

        // Build imports
        $importsString = '';
        foreach ($imports as $import) {
            $importsString .= "use {$import};\n";
        }

        // Build input schema
        $inputSchemaString = $this->arrayToPhpString($inputSchema, 2);

        // Build annotations
        $annotationsString = $this->arrayToPhpString($annotations, 2);

        // Replace placeholders in stub
        $replacements = [
            '{{ namespace }}' => 'App\\MCP\\Tools',
            '{{ className }}' => $className,
            '{{ toolName }}' => $toolName,
            '{{ description }}' => addslashes($description),
            '{{ inputSchema }}' => $inputSchemaString,
            '{{ annotations }}' => $annotationsString,
            '{{ executeLogic }}' => $executeLogic,
            '{{ imports }}' => $importsString,
        ];

        foreach ($replacements as $search => $replace) {
            $stub = str_replace($search, $replace, $stub);
        }

        return $stub;
    }

    /**
     * Convert array to PHP string representation
     */
    protected function arrayToPhpString(array $array, int $indent = 0): string
    {
        if (empty($array)) {
            return '[]';
        }

        $json = json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Convert JSON to PHP array syntax
        $php = preg_replace('/^(\s*)"([^"]+)"\s*:/m', '$1\'$2\' =>', $json);
        $php = str_replace('{', '[', $php);
        $php = str_replace('}', ']', $php);
        $php = preg_replace('/: true/', '=> true', $php);
        $php = preg_replace('/: false/', '=> false', $php);
        $php = preg_replace('/: null/', '=> null', $php);
        $php = preg_replace('/: (\d+)/', '=> $1', $php);
        $php = preg_replace('/: (\d+\.\d+)/', '=> $1', $php);

        // Add indentation
        $lines = explode("\n", $php);
        $indentStr = str_repeat('    ', $indent);
        $php = implode("\n".$indentStr, $lines);

        return $php;
    }

    /**
     * Get the stub file path.
     *
     * @return string
     */
    protected function getStubPath()
    {
        return __DIR__.'/../../stubs/tool.stub';
    }

    /**
     * Replace the stub placeholders with actual values.
     *
     * @return string
     */
    protected function replaceStubPlaceholders(string $stub, string $className, string $toolName)
    {
        return str_replace(
            ['{{ className }}', '{{ namespace }}', '{{ toolName }}'],
            [$className, 'App\\MCP\\Tools', $toolName],
            $stub
        );
    }

    /**
     * Register the tool in the MCP server configuration file.
     *
     * @param  string  $toolClassName  Fully qualified class name of the tool
     * @return bool Whether registration was successful
     */
    protected function registerToolInConfig(string $toolClassName): bool
    {
        $configPath = config_path('mcp-server.php');

        if (! file_exists($configPath)) {
            $this->error("❌ Config file not found: {$configPath}");

            return false;
        }

        $content = file_get_contents($configPath);

        // Find the tools array in the config file
        if (! preg_match('/[\'"]tools[\'"]\s*=>\s*\[(.*?)\s*\],/s', $content, $matches)) {
            $this->error('❌ Could not locate tools array in config file.');

            return false;
        }

        $toolsArrayContent = $matches[1];
        $fullEntry = "\n        {$toolClassName}::class,";

        // Check if the tool is already registered
        if (strpos($toolsArrayContent, $toolClassName) !== false) {
            $this->info('✅ Tool is already registered in config file.');

            return true;
        }

        // Add the new tool to the tools array
        $newToolsArrayContent = $toolsArrayContent.$fullEntry;
        $newContent = str_replace($toolsArrayContent, $newToolsArrayContent, $content);

        // Write the updated content back to the config file
        if (file_put_contents($configPath, $newContent)) {
            $this->info('✅ Tool registered successfully in config/mcp-server.php');

            return true;
        } else {
            $this->error('❌ Failed to update config file. Please manually register the tool.');

            return false;
        }
    }
}
