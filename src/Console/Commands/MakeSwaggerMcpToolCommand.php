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
                            {--group-by= : Group endpoints by tag or path (tag|path|none)}
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

    /**
     * Selected endpoints with their generation type
     */
    protected array $selectedEndpointsWithType = [];

    /**
     * Selected grouping method
     */
    protected string $groupingMethod;

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
            // Set grouping method for non-interactive mode
            $this->groupingMethod = $this->getGroupingOption();

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

        $this->groupingMethod = $this->getGroupingOption();

        if ($this->groupingMethod === 'tag') {
            $this->selectByTagWithTypes();
        } elseif ($this->groupingMethod === 'path') {
            $this->selectByPathWithTypes();
        } else {
            $this->selectIndividuallyWithTypes();
        }

        $toolCount = count(array_filter($this->selectedEndpointsWithType, fn ($e) => $e['type'] === 'tool'));
        $resourceCount = count(array_filter($this->selectedEndpointsWithType, fn ($e) => $e['type'] === 'resource'));

        $this->info("Selected {$toolCount} tools and {$resourceCount} resources.");
    }

    /**
     * Get the grouping option - prompt user if not provided
     */
    protected function getGroupingOption(): string
    {
        $groupBy = $this->option('group-by');

        // If grouping option is provided, return it
        if ($groupBy) {
            return $groupBy;
        }

        // If no interaction is disabled or option not provided, ask user interactively
        if (! $this->option('no-interaction')) {
            $this->newLine();
            $this->info('🗂️ Choose how to organize your generated tools and resources:');
            $this->newLine();

            // Generate previews for each grouping option
            $previews = $this->generateGroupingPreviews();

            $choices = [
                'tag' => 'Tag-based grouping (organize by OpenAPI tags)',
                'path' => 'Path-based grouping (organize by API path)',
                'none' => 'No grouping (everything in General/ folder)',
            ];

            // Display previews
            foreach ($choices as $key => $description) {
                $this->line("<options=bold>{$description}</>");
                if (! empty($previews[$key])) {
                    foreach ($previews[$key] as $example) {
                        $this->line("  <fg=cyan>📁 {$example}</>");
                    }
                } else {
                    $this->line('  <fg=yellow>No examples available</>');
                }
                $this->newLine();
            }

            $choice = $this->choice(
                'Select grouping method',
                array_values($choices),
                0  // Default to first option (tag-based)
            );

            // Map choice back to key
            $groupBy = array_search($choice, $choices);

            $this->info("Selected: {$choice}");
            $this->newLine();
        } else {
            // Default to 'tag' for non-interactive mode
            $groupBy = 'tag';
        }

        return $groupBy;
    }

    /**
     * Generate grouping previews to show users examples of how endpoints will be organized
     */
    protected function generateGroupingPreviews(): array
    {
        $previews = [
            'tag' => [],
            'path' => [],
            'none' => ['Tools/General/YourEndpointTool.php', 'Resources/General/YourEndpointResource.php'],
        ];

        // Get sample endpoints (max 5 per grouping type for clean display)
        $endpoints = $this->parser->getEndpoints();
        $sampleEndpoints = array_slice($endpoints, 0, 8); // Get first 8 endpoints

        // Generate tag-based previews
        $tagGroups = [];
        foreach ($sampleEndpoints as $endpoint) {
            if (! empty($endpoint['tags'])) {
                $tag = $endpoint['tags'][0];
                $directory = $this->createTagDirectory($endpoint);
                if (! isset($tagGroups[$directory])) {
                    $tagGroups[$directory] = [];
                }

                // Create example file names
                $className = $this->converter->generateClassName($endpoint, '');
                $type = $endpoint['method'] === 'GET' ? 'Resources' : 'Tools';
                $tagGroups[$directory][] = "{$type}/{$directory}/{$className}.php";
            }
        }

        // Limit to 4 most populated tag groups for display
        $tagGroups = array_slice($tagGroups, 0, 4, true);
        foreach ($tagGroups as $examples) {
            $previews['tag'] = array_merge($previews['tag'], array_slice($examples, 0, 2));
        }

        // Generate path-based previews
        $pathGroups = [];
        foreach ($sampleEndpoints as $endpoint) {
            $directory = $this->createPathDirectory($endpoint);
            if (! isset($pathGroups[$directory])) {
                $pathGroups[$directory] = [];
            }

            $className = $this->converter->generateClassName($endpoint, '');
            $type = $endpoint['method'] === 'GET' ? 'Resources' : 'Tools';
            $pathGroups[$directory][] = "{$type}/{$directory}/{$className}.php";
        }

        // Limit to 4 most populated path groups for display
        $pathGroups = array_slice($pathGroups, 0, 4, true);
        foreach ($pathGroups as $examples) {
            $previews['path'] = array_merge($previews['path'], array_slice($examples, 0, 2));
        }

        // Limit each preview to 6 items max for clean display
        foreach ($previews as $key => $items) {
            if (count($items) > 6) {
                $previews[$key] = array_slice($items, 0, 5);
                $previews[$key][] = '... and more';
            }
        }

        return $previews;
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

                // Create directory structure based on grouping strategy
                $directory = $this->createDirectory($endpoint);
                $path = app_path("MCP/Tools/{$directory}/{$className}.php");

                if (file_exists($path)) {
                    $this->warn("Skipping {$className} - already exists");

                    continue;
                }

                $this->line("Generating Tool: {$className}");

                // Get tool parameters
                $toolParams = $this->converter->convertEndpointToTool($endpoint, $className);

                // Add tag directory to tool params for namespace handling
                $toolParams['tagDirectory'] = $directory;

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

                // Create directory structure based on grouping strategy
                $directory = $this->createDirectory($endpoint);
                $path = app_path("MCP/Resources/{$directory}/{$className}.php");

                if (file_exists($path)) {
                    $this->warn("Skipping {$className} - already exists");

                    continue;
                }

                $this->line("Generating Resource: {$className}");

                // Get resource parameters
                $resourceParams = $this->converter->convertEndpointToResource($endpoint, $className);

                // Add tag directory to resource params for namespace handling
                $resourceParams['tagDirectory'] = $directory;

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
     * Create a directory name based on grouping strategy
     */
    protected function createDirectory(array $endpoint): string
    {
        switch ($this->groupingMethod) {
            case 'tag':
                return $this->createTagDirectory($endpoint);
            case 'path':
                return $this->createPathDirectory($endpoint);
            default:
                return 'General';
        }
    }

    /**
     * Create a tag-based directory name from endpoint tags
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

    /**
     * Create a path-based directory name from endpoint path
     */
    protected function createPathDirectory(array $endpoint): string
    {
        $path = $endpoint['path'] ?? '';
        $parts = explode('/', trim($path, '/'));

        // Use the first path segment, or 'Root' if no segments
        $firstSegment = ! empty($parts[0]) ? $parts[0] : 'Root';

        // Convert to StudlyCase for directory naming
        return Str::studly($firstSegment);
    }
}
