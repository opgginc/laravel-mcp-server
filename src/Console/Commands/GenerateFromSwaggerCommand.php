<?php

namespace OPGG\LaravelMcpServer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use OPGG\LaravelMcpServer\Services\SwaggerParser;
use OPGG\LaravelMcpServer\Services\ToolGenerator;

class GenerateFromSwaggerCommand extends Command
{
    protected $signature = 'mcp:generate-from-swagger {source : Path to Swagger/OpenAPI JSON file or URL}
                            {--output-dir=app/MCP/Tools/Swagger : Custom output directory}
                            {--base-url-env=SWAGGER_API_BASE_URL : Environment variable name for API base URL}
                            {--no-register : Skip auto-registration in config}
                            {--force : Overwrite existing files without confirmation}';

    protected $description = 'Generate MCP tools from Swagger/OpenAPI JSON specification';

    protected SwaggerParser $parser;

    protected ToolGenerator $generator;

    public function __construct(SwaggerParser $parser, ToolGenerator $generator)
    {
        parent::__construct();
        $this->parser = $parser;
        $this->generator = $generator;
    }

    public function handle(): int
    {
        $source = $this->argument('source');

        // Check if source is a URL
        if (filter_var($source, FILTER_VALIDATE_URL)) {
            $this->info('Downloading Swagger/OpenAPI specification from URL...');
            $json = $this->downloadFromUrl($source);

            if ($json === null) {
                return Command::FAILURE;
            }
        } else {
            // Treat as file path
            if (! File::exists($source)) {
                $this->error("File not found: {$source}");

                return Command::FAILURE;
            }

            $json = File::get($source);
        }

        // Parse JSON
        $spec = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON file: '.json_last_error_msg());

            return Command::FAILURE;
        }

        $this->info('Parsing Swagger/OpenAPI specification...');

        try {
            // Parse the specification
            $endpoints = $this->parser->parse($spec);

            if (empty($endpoints)) {
                $this->warn('No endpoints found in the specification.');

                return Command::SUCCESS;
            }

            $endpointCount = count($endpoints);
            $this->info("Found {$endpointCount} endpoints to process.");

            // Setup output directory
            $outputDir = base_path($this->option('output-dir'));
            if (! File::exists($outputDir)) {
                File::makeDirectory($outputDir, 0755, true);
            }

            // Generate tools
            $generatedTools = [];
            $progressBar = $this->output->createProgressBar(count($endpoints));
            $progressBar->start();

            foreach ($endpoints as $index => $endpoint) {
                $this->newLine();
                $current = $index + 1;
                $total = count($endpoints);
                $this->info("Processing endpoint [{$current}/{$total}]: {$endpoint['method']} {$endpoint['path']}");

                $toolClass = $this->generator->generateTool(
                    $endpoint,
                    $outputDir,
                    $this->option('base-url-env'),
                    $this->option('force')
                );

                if ($toolClass) {
                    $generatedTools[] = $toolClass;
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            // Register tools if needed
            if (! $this->option('no-register') && ! empty($generatedTools)) {
                $this->info('Registering tools in configuration...');
                $registered = $this->registerTools($generatedTools);

                if ($registered > 0) {
                    $this->info("Successfully registered {$registered} tools.");
                }
            }

            // Display summary
            $this->displaySummary(count($endpoints), count($generatedTools));

            // Generate .env.example entry
            $this->generateEnvExample($this->option('base-url-env'));

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error processing specification: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    protected function registerTools(array $toolClasses): int
    {
        $configPath = config_path('mcp-server.php');

        if (! File::exists($configPath)) {
            $this->warn('Config file not found. Please publish the configuration first.');

            return 0;
        }

        $config = File::get($configPath);
        $registered = 0;

        // Find the tools array
        if (preg_match('/\'tools\'\s*=>\s*\[/', $config, $matches, PREG_OFFSET_CAPTURE)) {
            $insertPosition = $matches[0][1] + strlen($matches[0][0]);

            // Check for existing tools and find the right position
            $afterBracket = substr($config, $insertPosition);

            // Add comment for generated tools
            $toolsToAdd = "\n        // Generated from Swagger";

            foreach ($toolClasses as $className) {
                // Check if already registered
                if (strpos($config, $className) === false) {
                    $toolsToAdd .= "\n        \\{$className}::class,";
                    $registered++;
                } else {
                    $this->warn("Tool already registered: {$className}");
                }
            }

            if ($registered > 0) {
                // Insert the new tools
                $newConfig = substr($config, 0, $insertPosition).$toolsToAdd.substr($config, $insertPosition);
                File::put($configPath, $newConfig);
            }
        }

        return $registered;
    }

    protected function displaySummary(int $totalEndpoints, int $generatedTools): void
    {
        $this->newLine();
        $this->info('=== Generation Summary ===');
        $this->line("Total endpoints processed: {$totalEndpoints}");
        $this->line("Tools generated: {$generatedTools}");

        if ($generatedTools < $totalEndpoints) {
            $skipped = $totalEndpoints - $generatedTools;
            $this->warn("Tools skipped: {$skipped} (due to conflicts or errors)");
        }
    }

    protected function generateEnvExample(string $envVar): void
    {
        $envExamplePath = base_path('.env.example');

        if (! File::exists($envExamplePath)) {
            return;
        }

        $envContent = File::get($envExamplePath);

        if (strpos($envContent, $envVar) === false) {
            $addition = "\n# Swagger API Configuration\n{$envVar}=https://api.example.com\n";
            File::append($envExamplePath, $addition);
            $this->info("Added {$envVar} to .env.example");
        }
    }

    protected function downloadFromUrl(string $url): ?string
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'Laravel-MCP-Server/1.0',
                ])
                ->get($url);

            if ($response->successful()) {
                $this->info('Successfully downloaded specification from URL');

                return $response->body();
            }

            $this->error("Failed to download from URL. Status: {$response->status()}");

            if ($response->status() === 404) {
                $this->error('The URL was not found. Please check the URL and try again.');
            } elseif ($response->status() === 403) {
                $this->error('Access denied. The URL may require authentication.');
            } elseif ($response->status() >= 500) {
                $this->error('Server error. Please try again later.');
            }

            return null;
        } catch (\Exception $e) {
            $this->error('Error downloading from URL: '.$e->getMessage());

            if (str_contains($e->getMessage(), 'cURL error 6')) {
                $this->error('Could not resolve host. Please check your internet connection and the URL.');
            } elseif (str_contains($e->getMessage(), 'cURL error 28')) {
                $this->error('Request timed out. The server may be slow or unreachable.');
            }

            return null;
        }
    }
}
