<?php

namespace OPGG\LaravelMcpServer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeMcpNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:mcp-notification {name : The name of the MCP notification handler} {--method= : The notification method to handle}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new MCP notification handler class';

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
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $className = $this->getClassName();
        $path = $this->getPath($className);
        $method = $this->getNotificationMethod();

        // Check if file already exists
        if ($this->files->exists($path)) {
            $this->error("❌ MCP notification handler {$className} already exists!");

            return 1;
        }

        // Create directories if they don't exist
        $this->makeDirectory($path);

        // Generate the file using stub
        $this->files->put($path, $this->buildClass($className, $method));

        $this->info("✅ Created: {$path}");

        $fullClassName = "\\App\\MCP\\Notifications\\{$className}";

        // Ask if they want to automatically register the notification handler
        if ($this->confirm('🤖 Would you like to automatically register this notification handler in your MCP server?', true)) {
            $this->info('☑️ Add this to your MCPServer registration:');
            $this->comment('    // In your service provider or server setup');
            $this->comment("    \$server->registerNotificationHandler(new {$fullClassName}());");
        } else {
            $this->info("☑️ Don't forget to register your notification handler:");
            $this->comment('    // In your service provider or server setup');
            $this->comment("    \$server->registerNotificationHandler(new {$fullClassName}());");
        }

        // Display usage instructions
        $this->newLine();
        $this->info('📋 Your notification handler overview:');
        $this->comment("    • Method: {$method}");
        $this->comment('    • Returns: HTTP 202 (no response body)');
        $this->comment('    • Purpose: Fire-and-forget event processing');

        $this->newLine();
        $this->info('📡 Clients can send this notification via JSON-RPC:');
        $this->comment('    {');
        $this->comment('        "jsonrpc": "2.0",');
        $this->comment("        \"method\": \"{$method}\",");
        $this->comment('        "params": {');
        $this->comment('            "key": "value",');
        $this->comment('            "data": { ... }');
        $this->comment('        }');
        $this->comment('    }');

        $this->newLine();
        $this->info('💡 Common notification use cases:');
        $this->comment('    • Progress updates for long-running tasks');
        $this->comment('    • Event logging and activity tracking');
        $this->comment('    • Real-time notifications and broadcasts');
        $this->comment('    • Background job triggering');
        $this->comment('    • Request cancellation handling');

        $this->newLine();
        $this->info('🧪 Test your notification handler:');
        $this->comment('    curl -X POST http://localhost:8000/mcp \\');
        $this->comment('         -H "Content-Type: application/json" \\');
        $this->comment('         -H "Accept: application/json, text/event-stream" \\');
        $this->comment("         -d '{\"jsonrpc\":\"2.0\",\"method\":\"{$method}\",\"params\":{}}'");
        $this->comment('    # Should return: HTTP 202 with empty body');

        return 0;
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

        // Ensure the class name ends with "Handler" if not already
        if (! Str::endsWith($name, 'Handler')) {
            $name .= 'Handler';
        }

        return $name;
    }

    /**
     * Get the notification method from option or ask user.
     *
     * @return string
     */
    protected function getNotificationMethod()
    {
        $method = $this->option('method');

        if (! $method) {
            $method = $this->ask('What notification method should this handler process? (e.g., notifications/progress)');
        }

        // Ensure it starts with 'notifications/' if not already
        if (! Str::startsWith($method, 'notifications/')) {
            $method = 'notifications/'.ltrim($method, '/');
        }

        return $method;
    }

    /**
     * Get the destination file path.
     *
     * @return string
     */
    protected function getPath(string $className)
    {
        // Create the file in the app/MCP/Notifications directory
        return app_path("MCP/Notifications/{$className}.php");
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
    protected function buildClass(string $className, string $method)
    {
        $stub = $this->files->get($this->getStubPath());

        return $this->replaceStubPlaceholders($stub, $className, $method);
    }

    /**
     * Get the stub file path.
     *
     * @return string
     */
    protected function getStubPath()
    {
        return __DIR__.'/../../stubs/notification.stub';
    }

    /**
     * Replace the stub placeholders with actual values.
     *
     * @return string
     */
    protected function replaceStubPlaceholders(string $stub, string $className, string $method)
    {
        return str_replace(
            ['{{ class }}', '{{ namespace }}', '{{ method }}'],
            [$className, 'App\\MCP\\Notifications', $method],
            $stub
        );
    }
}
