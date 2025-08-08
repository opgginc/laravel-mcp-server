<?php

namespace OPGG\LaravelMcpServer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use OPGG\LaravelMcpServer\Services\SwaggerParser\SwaggerParser;
use OPGG\LaravelMcpServer\Services\SwaggerParser\SwaggerToMcpConverter;

class MakeSwaggerMcpToolCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:swagger-mcp-tool {source : Swagger/OpenAPI spec URL or file path}
                            {--test-api : Test API endpoints before generating tools}
                            {--group-by=tag : Group endpoints by (tag|path|none)}
                            {--prefix= : Prefix for generated tool class names}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate MCP tools from Swagger/OpenAPI specification';

    protected SwaggerParser $parser;

    protected SwaggerToMcpConverter $converter;

    protected array $authConfig = [];

    protected array $selectedEndpoints = [];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🚀 Swagger/OpenAPI to MCP Tool Generator');
        $this->line('=========================================');

        try {
            // Step 1: Load and validate spec
            $this->loadSpec();

            // Step 2: Test API connection (optional)
            if ($this->option('test-api') && ! $this->option('no-interaction')) {
                $this->testApiConnection();
            }

            // Step 3: Select endpoints
            $this->selectEndpoints();

            // Step 4: Configure authentication
            if (! $this->option('no-interaction')) {
                $this->configureAuthentication();
            }

            // Step 5: Generate tools
            $this->generateTools();

            $this->info('✅ MCP tools generated successfully!');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Load and validate the Swagger/OpenAPI spec
     */
    protected function loadSpec(): void
    {
        $source = $this->argument('source');

        $this->info("📄 Loading spec from: {$source}");

        $this->parser = new SwaggerParser;
        $this->parser->load($source);

        $info = $this->parser->getInfo();

        $this->info('✅ Spec loaded successfully!');
        $this->table(
            ['Property', 'Value'],
            [
                ['Title', $info['title']],
                ['Version', $info['version']],
                ['Base URL', $info['baseUrl'] ?? 'Not specified'],
                ['Total Endpoints', $info['totalEndpoints']],
                ['Tags', implode(', ', $info['tags'])],
                ['Security', implode(', ', $info['securitySchemes'])],
            ]
        );

        // Ask to modify base URL if needed
        if (! $this->option('no-interaction') && $info['baseUrl']) {
            if ($this->confirm("Would you like to modify the base URL? Current: {$info['baseUrl']}", false)) {
                $newUrl = $this->ask('Enter new base URL');
                $this->parser->setBaseUrl($newUrl);
            }
        }

        $this->converter = new SwaggerToMcpConverter($this->parser);
    }

    /**
     * Test API connection
     */
    protected function testApiConnection(): void
    {
        $this->info('🔧 Testing API connection...');

        $baseUrl = $this->parser->getBaseUrl();
        if (! $baseUrl) {
            $this->warn('No base URL found. Skipping API test.');

            return;
        }

        // Find a simple GET endpoint to test
        $testEndpoint = null;
        foreach ($this->parser->getEndpoints() as $endpoint) {
            if ($endpoint['method'] === 'GET' && empty($endpoint['parameters'])) {
                $testEndpoint = $endpoint;
                break;
            }
        }

        if (! $testEndpoint) {
            // Try any GET endpoint
            foreach ($this->parser->getEndpoints() as $endpoint) {
                if ($endpoint['method'] === 'GET') {
                    $testEndpoint = $endpoint;
                    break;
                }
            }
        }

        if ($testEndpoint) {
            $url = $baseUrl.$testEndpoint['path'];
            $this->line("Testing: GET {$url}");

            try {
                $response = Http::timeout(10)->get($url);

                if ($response->successful()) {
                    $this->info("✅ API is accessible! Status: {$response->status()}");
                } else {
                    $this->warn("⚠️ API returned status {$response->status()}. This might be normal if authentication is required.");
                }
            } catch (\Exception $e) {
                $this->warn('⚠️ Could not connect to API: '.$e->getMessage());

                if ($this->confirm('Would you like to continue anyway?', true)) {
                    return;
                }

                throw $e;
            }
        } else {
            $this->warn('No suitable endpoint found for testing.');
        }
    }

    /**
     * Select endpoints to generate tools for
     */
    protected function selectEndpoints(): void
    {
        $endpoints = $this->parser->getEndpoints();

        if ($this->option('no-interaction')) {
            // In non-interactive mode, select all non-deprecated endpoints
            $this->selectedEndpoints = array_filter($endpoints, fn ($e) => ! $e['deprecated']);
            $this->info('Selected '.count($this->selectedEndpoints).' endpoints (excluding deprecated).');

            return;
        }

        $this->info('📋 Select endpoints to generate tools for:');

        $groupBy = $this->option('group-by');

        if ($groupBy === 'tag') {
            $this->selectByTag();
        } elseif ($groupBy === 'path') {
            $this->selectByPath();
        } else {
            $this->selectIndividually();
        }

        $this->info('Selected '.count($this->selectedEndpoints).' endpoints.');
    }

    /**
     * Select endpoints by tag
     */
    protected function selectByTag(): void
    {
        $byTag = $this->parser->getEndpointsByTag();

        foreach ($byTag as $tag => $endpoints) {
            $count = count($endpoints);
            $deprecated = count(array_filter($endpoints, fn ($e) => $e['deprecated']));

            $label = "{$tag} ({$count} endpoints";
            if ($deprecated > 0) {
                $label .= ", {$deprecated} deprecated";
            }
            $label .= ')';

            if ($this->confirm("Include tag: {$label}?", true)) {
                foreach ($endpoints as $endpoint) {
                    if (! $endpoint['deprecated'] || $this->confirm("Include deprecated: {$endpoint['method']} {$endpoint['path']}?", false)) {
                        $this->selectedEndpoints[] = $endpoint;
                    }
                }
            }
        }
    }

    /**
     * Select endpoints by path prefix
     */
    protected function selectByPath(): void
    {
        $byPath = [];

        foreach ($this->parser->getEndpoints() as $endpoint) {
            $parts = explode('/', trim($endpoint['path'], '/'));
            $prefix = ! empty($parts[0]) ? $parts[0] : 'root';
            if (! isset($byPath[$prefix])) {
                $byPath[$prefix] = [];
            }
            $byPath[$prefix][] = $endpoint;
        }

        foreach ($byPath as $prefix => $endpoints) {
            $count = count($endpoints);

            if ($this->confirm("Include path prefix '/{$prefix}' ({$count} endpoints)?", true)) {
                foreach ($endpoints as $endpoint) {
                    if (! $endpoint['deprecated'] || $this->confirm("Include deprecated: {$endpoint['method']} {$endpoint['path']}?", false)) {
                        $this->selectedEndpoints[] = $endpoint;
                    }
                }
            }
        }
    }

    /**
     * Select endpoints individually
     */
    protected function selectIndividually(): void
    {
        foreach ($this->parser->getEndpoints() as $endpoint) {
            $label = "{$endpoint['method']} {$endpoint['path']}";
            if ($endpoint['summary']) {
                $label .= " - {$endpoint['summary']}";
            }
            if ($endpoint['deprecated']) {
                $label .= ' [DEPRECATED]';
            }

            if ($this->confirm("Include: {$label}?", ! $endpoint['deprecated'])) {
                $this->selectedEndpoints[] = $endpoint;
            }
        }
    }

    /**
     * Configure authentication
     */
    protected function configureAuthentication(): void
    {
        $schemes = $this->parser->getSecuritySchemes();

        if (empty($schemes)) {
            $this->info('No security schemes found in spec.');

            return;
        }

        $this->info('🔐 Configure authentication:');

        foreach ($schemes as $name => $scheme) {
            $type = $scheme['type'] ?? 'unknown';

            $this->line("Security scheme: {$name} (type: {$type})");

            if ($type === 'apiKey') {
                $in = $scheme['in'] ?? 'header';
                $paramName = $scheme['name'] ?? 'X-API-Key';

                if ($this->confirm('Configure API Key authentication?', true)) {
                    $this->authConfig['api_key'] = [
                        'location' => $in,
                        'name' => $paramName,
                    ];

                    $this->info("API Key will be read from config('services.api.key')");
                    $this->line('Add to your .env: API_KEY=your-key-here');
                }
            } elseif ($type === 'http' && ($scheme['scheme'] ?? '') === 'bearer') {
                if ($this->confirm('Configure Bearer Token authentication?', true)) {
                    $this->authConfig['bearer_token'] = true;

                    $this->info("Bearer token will be read from config('services.api.token')");
                    $this->line('Add to your .env: API_TOKEN=your-token-here');
                }
            } elseif ($type === 'oauth2') {
                $this->warn('OAuth2 authentication detected. Manual configuration will be needed in generated tools.');
            }
        }

        if (! empty($this->authConfig)) {
            $this->converter->setAuthConfig($this->authConfig);
        }
    }

    /**
     * Generate MCP tools
     */
    protected function generateTools(): void
    {
        $this->info('🛠️ Generating MCP tools...');

        $prefix = $this->option('prefix');
        $generated = [];

        foreach ($this->selectedEndpoints as $endpoint) {
            // Debug: Check if operationId looks like a hash
            if (!empty($endpoint['operationId']) && preg_match('/^[a-f0-9]{32}$/i', $endpoint['operationId'])) {
                $this->comment("Note: operationId '{$endpoint['operationId']}' looks like a hash, will use path-based naming");
                // Clear the operationId to force path-based naming
                $endpoint['operationId'] = null;
            }
            
            $className = $this->converter->generateClassName($endpoint, $prefix);

            // Check if class already exists
            $path = app_path("MCP/Tools/{$className}.php");
            if (file_exists($path)) {
                $this->warn("Skipping {$className} - already exists");

                continue;
            }

            $this->line("Generating: {$className}");

            // Get tool parameters
            $toolParams = $this->converter->convertEndpointToTool($endpoint, $className);

            // Create the tool using MakeMcpToolCommand
            $makeTool = new MakeMcpToolCommand(app('files'));
            $makeTool->setLaravel($this->getLaravel());
            $makeTool->setDynamicParams($toolParams);

            // Create input for the command
            $input = new \Symfony\Component\Console\Input\ArrayInput([
                'name' => $className,
                '--programmatic' => true,
            ]);

            $output = new \Symfony\Component\Console\Output\NullOutput;

            try {
                $makeTool->run($input, $output);
                $generated[] = $className;
                $this->info("  ✅ Generated: {$className}");
            } catch (\Exception $e) {
                $this->error("  ❌ Failed to generate {$className}: ".$e->getMessage());
            }
        }

        if (! empty($generated)) {
            $this->newLine();
            $this->info('📦 Generated '.count($generated).' MCP tools:');
            foreach ($generated as $className) {
                $this->line("  - {$className}");
            }

            $this->newLine();
            $this->info('Next steps:');
            $this->line('1. Review the generated tools in app/MCP/Tools/');
            $this->line('2. Update authentication configuration if needed');
            $this->line('3. Test tools with: php artisan mcp:test-tool <ToolName>');
            $this->line('4. Tools have been automatically registered in config/mcp-server.php');
        } else {
            $this->warn('No tools were generated.');
        }
    }
}
