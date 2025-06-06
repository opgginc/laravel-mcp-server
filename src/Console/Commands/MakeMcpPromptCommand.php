<?php

namespace OPGG\LaravelMcpServer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeMcpPromptCommand extends Command
{
    protected $signature = 'make:mcp-prompt {name : The name of the prompt}';

    protected $description = 'Create a new MCP prompt class';

    public function __construct(private Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $className = $this->getClassName();
        $path = $this->getPath($className);

        if ($this->files->exists($path)) {
            $this->error("❌ MCP prompt {$className} already exists!");

            return 1;
        }

        $this->makeDirectory($path);
        $stub = $this->files->get(__DIR__.'/../../stubs/prompt.stub');
        $stub = str_replace(['{{ className }}', '{{ namespace }}'], [$className, 'App\\MCP\\Prompts'], $stub);
        $this->files->put($path, $stub);
        $this->info("✅ Created: {$path}");

        return 0;
    }

    protected function getClassName(): string
    {
        $name = preg_replace('/[\s\-_]+/', ' ', trim($this->argument('name')));
        $name = Str::studly($name);
        if (! Str::endsWith($name, 'Prompt')) {
            $name .= 'Prompt';
        }

        return $name;
    }

    protected function getPath(string $className): string
    {
        return app_path("MCP/Prompts/{$className}.php");
    }

    protected function makeDirectory(string $path): void
    {
        $dir = dirname($path);
        if (! $this->files->isDirectory($dir)) {
            $this->files->makeDirectory($dir, 0755, true, true);
        }
    }
}
