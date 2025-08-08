<?php

namespace OPGG\LaravelMcpServer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeMcpResourceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:mcp-resource {name : The name of the resource} {--programmatic : Use programmatic mode with dynamic parameters}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new MCP resource class';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Dynamic parameters for programmatic generation
     */
    protected array $dynamicParams = [];

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
            $this->error("❌ MCP resource {$className} already exists!");

            return 1;
        }

        // Create directories if they don't exist
        $this->makeDirectory($path);

        // Generate the file using stub
        $this->files->put($path, $this->buildClass($className));

        $this->info("✅ Created: {$path}");

        $fullClassName = "\\App\\MCP\\Resources\\{$className}";

        // Ask if they want to automatically register the resource (skip in programmatic mode)
        if (! $this->option('programmatic')) {
            if ($this->confirm('🤖 Would you like to automatically register this resource in config/mcp-server.php?', true)) {
                $this->registerResourceInConfig($fullClassName);
            } else {
                $this->info("☑️ Don't forget to register your resource in config/mcp-server.php:");
                $this->comment('    // config/mcp-server.php');
                $this->comment("    'resources' => [");
                $this->comment('        // other resources...');
                $this->comment("        {$fullClassName}::class,");
                $this->comment('    ],');
            }
        } else {
            // In programmatic mode, always register the resource
            $this->registerResourceInConfig($fullClassName);
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

        // Ensure the class name ends with "Resource" if not already
        if (! Str::endsWith($name, 'Resource')) {
            $name .= 'Resource';
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
        // Create the file in the app/MCP/Resources directory
        return app_path("MCP/Resources/{$className}.php");
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

        return $this->replaceStubPlaceholders($stub, $className);
    }

    /**
     * Build a class with dynamic parameters
     */
    protected function buildDynamicClass(string $className): string
    {
        // Load the programmatic stub
        $stub = $this->files->get(__DIR__.'/../../stubs/resource.programmatic.stub');

        $uri = $this->dynamicParams['uri'] ?? 'api://resource';
        $name = $this->dynamicParams['name'] ?? $className;
        $description = $this->dynamicParams['description'] ?? "Resource for {$className}";
        $mimeType = $this->dynamicParams['mimeType'] ?? 'application/json';
        $readLogic = $this->dynamicParams['readLogic'] ?? $this->getDefaultReadLogic();

        // Replace placeholders in stub
        $replacements = [
            '{{ namespace }}' => 'App\\MCP\\Resources',
            '{{ className }}' => $className,
            '{{ uri }}' => $uri,
            '{{ name }}' => addslashes($name),
            '{{ description }}' => addslashes($description),
            '{{ mimeType }}' => $mimeType,
            '{{ readLogic }}' => $readLogic,
        ];

        foreach ($replacements as $search => $replace) {
            $stub = str_replace($search, $replace, $stub);
        }

        return $stub;
    }

    /**
     * Get default read logic for the resource
     */
    protected function getDefaultReadLogic(): string
    {
        return <<<'PHP'
        try {
            // TODO: Implement your resource reading logic here
            $data = [
                'message' => 'Resource data placeholder',
                'timestamp' => now()->toISOString(),
            ];
            
            return [
                'uri' => $this->uri,
                'mimeType' => $this->mimeType,
                'text' => json_encode($data, JSON_PRETTY_PRINT),
            ];
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Failed to read resource {$this->uri}: " . $e->getMessage()
            );
        }
PHP;
    }

    /**
     * Get the stub file path.
     *
     * @return string
     */
    protected function getStubPath()
    {
        return __DIR__.'/../../stubs/resource.stub';
    }

    /**
     * Replace the stub placeholders with actual values.
     *
     * @return string
     */
    protected function replaceStubPlaceholders(string $stub, string $className)
    {
        return str_replace(
            ['{{ className }}', '{{ namespace }}'],
            [$className, 'App\\MCP\\Resources'],
            $stub
        );
    }

    /**
     * Register the resource in the MCP server configuration file.
     *
     * @param  string  $resourceClassName  Fully qualified class name of the resource
     * @return bool Whether registration was successful
     */
    protected function registerResourceInConfig(string $resourceClassName): bool
    {
        $configPath = config_path('mcp-server.php');

        if (! file_exists($configPath)) {
            $this->error("❌ Config file not found: {$configPath}");

            return false;
        }

        $content = file_get_contents($configPath);

        // Find the resources array in the config file
        if (! preg_match('/[\'"]resources[\'\"]\s*=>\s*\[([^\]]*)\]/s', $content, $matches)) {
            // Try to add resources array after tools array if it doesn't exist
            if (preg_match('/([\'"]tools[\'\"]\s*=>\s*\[.*?\s*\],)/s', $content, $toolsMatches)) {
                $toolsArray = $toolsMatches[1];
                $resourcesArray = "\n\n    // Resources - Static resources that expose data to LLMs\n    'resources' => [\n        {$resourceClassName}::class,\n    ],";
                $newContent = str_replace($toolsArray, "{$toolsArray}{$resourcesArray}", $content);

                if (file_put_contents($configPath, $newContent)) {
                    $this->info('✅ Created resources array and registered resource in config/mcp-server.php');

                    return true;
                } else {
                    $this->error('❌ Failed to update config file. Please manually register the resource.');

                    return false;
                }
            }

            $this->error('❌ Could not locate resources array in config file.');

            return false;
        }

        $resourcesArrayContent = $matches[1];

        // Check if the resource is already registered
        if (strpos($resourcesArrayContent, $resourceClassName) !== false) {
            $this->info('✅ Resource is already registered in config file.');

            return true;
        }

        // Handle empty array case
        $fullEntry = trim($resourcesArrayContent) === ''
            ? "\n        {$resourceClassName}::class,\n    "
            : "\n        {$resourceClassName}::class,";

        // Replace the entire resources array content
        $oldResourcesArray = "[{$resourcesArrayContent}]";
        $newResourcesArray = "[{$resourcesArrayContent}{$fullEntry}]";
        $newContent = str_replace($oldResourcesArray, $newResourcesArray, $content);

        // Write the updated content back to the config file
        if (file_put_contents($configPath, $newContent)) {
            $this->info('✅ Resource registered successfully in config/mcp-server.php');

            return true;
        } else {
            $this->error('❌ Failed to update config file. Please manually register the resource.');

            return false;
        }
    }
}
