<?php

namespace OPGG\LaravelMcpServer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
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
     * Selected endpoints with their generation type
     */
    protected array $selectedEndpointsWithType = [];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🚀 Swagger/OpenAPI to MCP Generator');
        $this->line('=========================================');

        try {
            // Step 1: Load and validate spec
            $this->loadSpec();

            // Step 2: Test API connection (optional)
            if ($this->option('test-api') && ! $this->option('no-interaction')) {
                $this->testApiConnection();
            }

            // Step 3: Select endpoints and their types
            $this->selectEndpointsWithTypes();

            // Step 4: Configure authentication
            if (! $this->option('no-interaction')) {
                $this->configureAuthentication();
            }

            // Step 5: Generate tools and resources
            $this->generateComponents();

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Select endpoints and their generation types
     */
    protected function selectEndpointsWithTypes(): void
    {
        $endpoints = $this->parser->getEndpoints();

        if ($this->option('no-interaction')) {
            // In non-interactive mode, use smart defaults
            foreach ($endpoints as $endpoint) {
                if ($endpoint['deprecated']) {
                    continue;
                }

                // GET endpoints become resources, others become tools
                $type = $endpoint['method'] === 'GET' ? 'resource' : 'tool';
                $this->selectedEndpointsWithType[] = [
                    'endpoint' => $endpoint,
                    'type' => $type,
                ];
            }

            $toolCount = count(array_filter($this->selectedEndpointsWithType, fn ($e) => $e['type'] === 'tool'));
            $resourceCount = count(array_filter($this->selectedEndpointsWithType, fn ($e) => $e['type'] === 'resource'));

            $this->info("Selected {$toolCount} tools and {$resourceCount} resources (excluding deprecated).");

            return;
        }

        $this->info('📋 Select endpoints and choose their generation type:');
        $this->newLine();
        $this->comment('Tip: GET endpoints are typically Resources, while POST/PUT/DELETE are Tools');
        $this->newLine();

        $groupBy = $this->option('group-by');

        if ($groupBy === 'tag') {
            $this->selectByTagWithTypes();
        } elseif ($groupBy === 'path') {
            $this->selectByPathWithTypes();
        } else {
            $this->selectIndividuallyWithTypes();
        }

        $toolCount = count(array_filter($this->selectedEndpointsWithType, fn ($e) => $e['type'] === 'tool'));
        $resourceCount = count(array_filter($this->selectedEndpointsWithType, fn ($e) => $e['type'] === 'resource'));

        $this->info("Selected {$toolCount} tools and {$resourceCount} resources.");
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
     * Select endpoints individually with type choice
     */
    protected function selectIndividuallyWithTypes(): void
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
                // Ask for type
                $defaultType = $endpoint['method'] === 'GET' ? 'Resource' : 'Tool';
                $typeChoice = $this->choice(
                    'Generate as',
                    ['Tool (for actions)', 'Resource (for read-only data)', 'Skip'],
                    $endpoint['method'] === 'GET' ? 1 : 0
                );

                if (! str_contains($typeChoice, 'Skip')) {
                    $type = str_contains($typeChoice, 'Tool') ? 'tool' : 'resource';

                    // Validate: only GET can be resources
                    if ($type === 'resource' && $endpoint['method'] !== 'GET') {
                        $this->warn('Only GET endpoints can be resources. Generating as Tool instead.');
                        $type = 'tool';
                    }

                    $this->selectedEndpointsWithType[] = [
                        'endpoint' => $endpoint,
                        'type' => $type,
                    ];
                }
            }
        }
    }

    /**
     * Select endpoints by tag with type choice
     */
    protected function selectByTagWithTypes(): void
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
                    if ($endpoint['deprecated'] && ! $this->confirm("Include deprecated: {$endpoint['method']} {$endpoint['path']}?", false)) {
                        continue;
                    }

                    // Smart default based on method
                    $defaultType = $endpoint['method'] === 'GET' ? 'resource' : 'tool';

                    $endpointLabel = "{$endpoint['method']} {$endpoint['path']}";
                    if ($endpoint['summary']) {
                        $endpointLabel .= " - {$endpoint['summary']}";
                    }

                    $this->info($endpointLabel);
                    $typeChoice = $this->choice(
                        'Generate as',
                        ['Tool', 'Resource', 'Skip'],
                        0
                    );

                    if ($typeChoice !== 'Skip') {
                        $type = strtolower($typeChoice);

                        // Validate: only GET can be resources
                        if ($type === 'resource' && $endpoint['method'] !== 'GET') {
                            $this->warn('Only GET endpoints can be resources. Generating as Tool instead.');
                            $type = 'tool';
                        }

                        $this->selectedEndpointsWithType[] = [
                            'endpoint' => $endpoint,
                            'type' => $type,
                        ];
                    }
                }
            }
        }
    }

    /**
     * Select endpoints by path with type choice
     */
    protected function selectByPathWithTypes(): void
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
                    if ($endpoint['deprecated'] && ! $this->confirm("Include deprecated: {$endpoint['method']} {$endpoint['path']}?", false)) {
                        continue;
                    }

                    $endpointLabel = "{$endpoint['method']} {$endpoint['path']}";
                    if ($endpoint['summary']) {
                        $endpointLabel .= " - {$endpoint['summary']}";
                    }

                    $this->info($endpointLabel);
                    $typeChoice = $this->choice(
                        'Generate as',
                        ['Tool', 'Resource', 'Skip'],
                        $endpoint['method'] === 'GET' ? 1 : 0
                    );

                    if ($typeChoice !== 'Skip') {
                        $type = strtolower($typeChoice);

                        // Validate: only GET can be resources
                        if ($type === 'resource' && $endpoint['method'] !== 'GET') {
                            $this->warn('Only GET endpoints can be resources. Generating as Tool instead.');
                            $type = 'tool';
                        }

                        $this->selectedEndpointsWithType[] = [
                            'endpoint' => $endpoint,
                            'type' => $type,
                        ];
                    }
                }
            }
        }
    }

    /**
     * Select endpoints to generate tools for (OLD - kept for compatibility)
     */
    protected function selectEndpoints(): void
    {
        $endpoints = $this->parser->getEndpoints();

        if ($this->option('no-interaction')) {
            // In non-interactive mode, select appropriate endpoints based on type
            if ($this->generateType === 'resource') {
                // For resources, only select GET endpoints
                $this->selectedEndpoints = array_filter($endpoints, fn ($e) => ! $e['deprecated'] && $e['method'] === 'GET');
                $this->info('Selected '.count($this->selectedEndpoints).' GET endpoints for resources (excluding deprecated).');
            } else {
                // For tools, select all non-deprecated endpoints
                $this->selectedEndpoints = array_filter($endpoints, fn ($e) => ! $e['deprecated']);
                $this->info('Selected '.count($this->selectedEndpoints).' endpoints (excluding deprecated).');
            }

            return;
        }

        $componentType = $this->generateType === 'resource' ? 'resources' : 'tools';
        $this->info("📋 Select endpoints to generate {$componentType} for:");

        if ($this->generateType === 'resource') {
            $this->comment('Note: Only GET endpoints can be converted to resources');
        }

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
     * Generate both tools and resources based on selected endpoints
     */
    protected function generateComponents(): void
    {
        $this->info('🛠️ Generating MCP components...');

        $prefix = $this->option('prefix');
        $generatedTools = [];
        $generatedResources = [];

        foreach ($this->selectedEndpointsWithType as $item) {
            $endpoint = $item['endpoint'];
            $type = $item['type'];

            // Check if operationId looks like a hash
            if (! empty($endpoint['operationId']) && preg_match('/^[a-f0-9]{32}$/i', $endpoint['operationId'])) {
                $this->comment("Note: operationId '{$endpoint['operationId']}' looks like a hash, will use path-based naming");
                $endpoint['operationId'] = null;
            }

            if ($type === 'tool') {
                // Generate tool
                $className = $this->converter->generateClassName($endpoint, $prefix);
                
                // Create tag-based directory structure
                $tagDirectory = $this->createTagDirectory($endpoint);
                $path = app_path("MCP/Tools/{$tagDirectory}/{$className}.php");

                if (file_exists($path)) {
                    $this->warn("Skipping {$className} - already exists");

                    continue;
                }

                $this->line("Generating Tool: {$className}");

                // Get tool parameters
                $toolParams = $this->converter->convertEndpointToTool($endpoint, $className);
                
                // Add tag directory to tool params for namespace handling
                $toolParams['tagDirectory'] = $tagDirectory;

                // Create the tool using MakeMcpToolCommand
                $makeTool = new MakeMcpToolCommand(app('files'));
                $makeTool->setLaravel($this->getLaravel());
                $makeTool->setDynamicParams($toolParams);

                $input = new \Symfony\Component\Console\Input\ArrayInput([
                    'name' => $className,
                    '--programmatic' => true,
                ]);

                $output = new \Symfony\Component\Console\Output\NullOutput;

                try {
                    $makeTool->run($input, $output);
                    $generatedTools[] = $className;
                    $this->info("  ✅ Generated Tool: {$className}");
                } catch (\Exception $e) {
                    $this->error("  ❌ Failed to generate {$className}: ".$e->getMessage());
                }

            } else {
                // Generate resource
                $className = $this->converter->generateResourceClassName($endpoint, $prefix);
                
                // Create tag-based directory structure
                $tagDirectory = $this->createTagDirectory($endpoint);
                $path = app_path("MCP/Resources/{$tagDirectory}/{$className}.php");

                if (file_exists($path)) {
                    $this->warn("Skipping {$className} - already exists");

                    continue;
                }

                $this->line("Generating Resource: {$className}");

                // Get resource parameters
                $resourceParams = $this->converter->convertEndpointToResource($endpoint, $className);
                
                // Add tag directory to resource params for namespace handling
                $resourceParams['tagDirectory'] = $tagDirectory;

                // Create the resource using MakeMcpResourceCommand
                $makeResource = new MakeMcpResourceCommand(app('files'));
                $makeResource->setLaravel($this->getLaravel());
                $makeResource->setDynamicParams($resourceParams);

                $input = new \Symfony\Component\Console\Input\ArrayInput([
                    'name' => $className,
                    '--programmatic' => true,
                ]);

                $output = new \Symfony\Component\Console\Output\NullOutput;

                try {
                    $makeResource->run($input, $output);
                    $generatedResources[] = $className;
                    $this->info("  ✅ Generated Resource: {$className}");
                } catch (\Exception $e) {
                    $this->error("  ❌ Failed to generate {$className}: ".$e->getMessage());
                }
            }
        }

        // Show summary
        $this->newLine();

        if (! empty($generatedTools)) {
            $this->info('📦 Generated '.count($generatedTools).' MCP tools:');
            foreach ($generatedTools as $className) {
                $this->line("  - {$className}");
            }
        }

        if (! empty($generatedResources)) {
            $this->info('📦 Generated '.count($generatedResources).' MCP resources:');
            foreach ($generatedResources as $className) {
                $this->line("  - {$className}");
            }
        }

        if (empty($generatedTools) && empty($generatedResources)) {
            $this->warn('No components were generated.');
        } else {
            $this->newLine();
            $this->info('✅ MCP components generated successfully!');
            $this->newLine();
            $this->info('Next steps:');

            $stepNumber = 1;

            if (! empty($generatedTools)) {
                $this->line("{$stepNumber}. Review generated tools in app/MCP/Tools/");
                $stepNumber++;
                $this->line("{$stepNumber}. Test tools with: php artisan mcp:test-tool <ToolName>");
                $stepNumber++;
            }

            if (! empty($generatedResources)) {
                $this->line("{$stepNumber}. Review generated resources in app/MCP/Resources/");
                $stepNumber++;
                $this->line("{$stepNumber}. Test resources with the MCP Inspector or client");
                $stepNumber++;
            }

            $this->line("{$stepNumber}. All components have been automatically registered in config/mcp-server.php");
            $stepNumber++;
            $this->line("{$stepNumber}. Update authentication configuration if needed");
        }
    }

    /**
     * Create a tag directory name from endpoint tags
     */
    protected function createTagDirectory(array $endpoint): string
    {
        // Get the first tag, or use a default
        $tags = $endpoint['tags'] ?? [];
        if (empty($tags)) {
            return 'General';
        }
        
        $tag = $tags[0]; // Use the first tag
        
        // Convert tag to StudlyCase for directory naming
        return Str::studly($tag);
    }
}
