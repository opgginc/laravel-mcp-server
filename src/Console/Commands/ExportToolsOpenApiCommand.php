<?php

namespace OPGG\LaravelMcpServer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use OPGG\LaravelMcpServer\JsonSchema\Types\Type;
use OPGG\LaravelMcpServer\Routing\McpEndpointDefinition;
use OPGG\LaravelMcpServer\Routing\McpEndpointRegistry;
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;
use OPGG\LaravelMcpServer\Utils\JsonSchemaNormalizer;
use stdClass;

class ExportToolsOpenApiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:export-openapi
                            {--output= : Output file path (default: storage/api-docs-mcp/api-docs.json)}
                            {--title= : OpenAPI info.title (default: MCP Tools API)}
                            {--api-version= : OpenAPI info.version (default: 1.0.0)}
                            {--server-url= : Optional server URL for OpenAPI servers[]}
                            {--discover-path=* : Additional directories to discover ToolInterface classes}
                            {--endpoint= : Limit tools to a specific MCP endpoint path or endpoint id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export registered MCP ToolInterface definitions to an OpenAPI JSON file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $outputPath = $this->resolveOutputPath();

        $toolEntries = $this->resolveTools();
        if ($toolEntries === []) {
            $this->warn(
                'No MCP tools are available for API export. '.
                'Use Route::mcp(...)->enabledApi()->tools([...]) to register endpoint tools.'
            );

            return 0;
        }

        $document = $this->buildOpenApiDocument($toolEntries);
        $encoded = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            $this->error('Failed to encode OpenAPI JSON document.');

            return 1;
        }

        $directory = dirname($outputPath);
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($outputPath, $encoded.PHP_EOL);
        $this->info('Generated OpenAPI spec for '.count($toolEntries).' tool(s): '.$outputPath);

        return 0;
    }

    /**
     * @param  array<int, array{tool: ToolInterface, tags: array<int, string>, compactEnumExampleCount: int}>  $toolEntries
     * @return array<string, mixed>
     */
    protected function buildOpenApiDocument(array $toolEntries): array
    {
        $schemaNames = [];
        $outputSchemaNames = [];
        $operationIds = [];
        $paths = [];
        $documentTags = [];
        $components = ['schemas' => []];

        foreach ($toolEntries as $toolEntry) {
            $tool = $toolEntry['tool'];
            $tags = $toolEntry['tags'];
            $compactEnumExampleCount = $toolEntry['compactEnumExampleCount'];
            $toolName = $tool->name();
            $path = '/tools/'.rawurlencode($toolName);
            $inputSchemaName = $this->uniqueSchemaName($toolName, 'Input', $schemaNames);
            $inputSchema = $this->normalizeInputSchema($tool->inputSchema(), $compactEnumExampleCount);

            $components['schemas'][$inputSchemaName] = $inputSchema;

            $outputSchemaRef = $this->buildOutputSchemaRef(
                $tool,
                $toolName,
                $components['schemas'],
                $outputSchemaNames,
                $compactEnumExampleCount
            );
            $operationId = $this->uniqueOperationId($toolName, $operationIds);

            $summary = $this->extractToolTitle($tool) ?? Str::headline($toolName);
            $parameters = $this->extractOperationParameters($inputSchema);

            $operation = [
                'operationId' => $operationId,
                'tags' => $tags,
                'summary' => $summary,
                'description' => $tool->description(),
                'responses' => [
                    '200' => [
                        'description' => 'Tool executed successfully.',
                        'content' => [
                            'application/json' => [
                                'schema' => $outputSchemaRef,
                            ],
                        ],
                    ],
                ],
            ];

            if ($parameters !== []) {
                $operation['parameters'] = $parameters;
            }

            $paths[$path] = [
                'post' => [
                    ...$operation,
                ],
            ];

            foreach ($tags as $tag) {
                $documentTags[$tag] = $tag;
            }
        }

        $document = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => $this->resolveTitle(),
                'version' => $this->resolveVersion(),
            ],
            'paths' => $paths,
            'components' => $components,
        ];

        $serverUrl = $this->option('server-url');
        if (is_string($serverUrl) && trim($serverUrl) !== '') {
            $document['servers'] = [
                ['url' => trim($serverUrl)],
            ];
        }

        if ($documentTags !== []) {
            $document['tags'] = array_map(
                fn (string $tagName) => ['name' => $tagName],
                array_values($documentTags)
            );
        }

        return $document;
    }

    /**
     * @param  array<string, mixed>  $schemas
     * @param  array<string, bool>  $usedSchemaNames
     * @return array<string, mixed>
     */
    protected function buildOutputSchemaRef(
        ToolInterface $tool,
        string $toolName,
        array &$schemas,
        array &$usedSchemaNames,
        int $compactEnumExampleCount
    ): array {
        $outputSchema = $this->extractOutputSchema($tool);
        if (! is_array($outputSchema) || $outputSchema === []) {
            return [
                'type' => 'object',
                'additionalProperties' => true,
            ];
        }

        $outputSchema = JsonSchemaNormalizer::normalize($outputSchema, $compactEnumExampleCount);
        if ($outputSchema === []) {
            return [
                'type' => 'object',
                'additionalProperties' => true,
            ];
        }

        $outputSchemaName = $this->uniqueSchemaName($toolName, 'Output', $usedSchemaNames);
        $schemas[$outputSchemaName] = $this->normalizeSchema($outputSchema);

        return [
            '$ref' => "#/components/schemas/{$outputSchemaName}",
        ];
    }

    /**
     * @return array<int, array{tool: ToolInterface, tags: array<int, string>, compactEnumExampleCount: int}>
     */
    protected function resolveTools(): array
    {
        $registeredTools = $this->resolveRegisteredTools();
        if ($registeredTools !== []) {
            return $registeredTools;
        }

        if ($this->shouldSkipDiscoveryFallback()) {
            return [];
        }

        return $this->resolveDiscoveredTools();
    }

    /**
     * @return array<int, array{tool: ToolInterface, tags: array<int, string>, compactEnumExampleCount: int}>
     */
    protected function resolveRegisteredTools(): array
    {
        /** @var McpEndpointRegistry $registry */
        $registry = app(McpEndpointRegistry::class);

        $endpointFilter = $this->option('endpoint');
        $needle = is_string($endpointFilter) && trim($endpointFilter) !== '' ? trim($endpointFilter) : null;

        $matchedEndpoint = false;
        $matchedApiEnabledEndpoint = false;
        /** @var array<string, array{tool: ToolInterface, tags: array<int, string>, compactEnumExampleCount: int}> $entriesByToolName */
        $entriesByToolName = [];

        foreach ($registry->all() as $definition) {
            if ($needle !== null && ! $this->matchesEndpoint($definition, $needle)) {
                continue;
            }

            $matchedEndpoint = true;
            if (! $definition->enabledApi) {
                continue;
            }

            $matchedApiEnabledEndpoint = true;
            $tag = $this->resolveEndpointTag($definition);

            foreach ($definition->tools as $toolClass) {
                $tool = $this->resolveToolInstance($toolClass);
                if ($tool === null) {
                    continue;
                }

                $toolName = $tool->name();
                if (! isset($entriesByToolName[$toolName])) {
                    $entriesByToolName[$toolName] = [
                        'tool' => $tool,
                        'tags' => [$tag],
                        'compactEnumExampleCount' => $definition->compactEnumExampleCount,
                    ];

                    continue;
                }

                if (! in_array($tag, $entriesByToolName[$toolName]['tags'], true)) {
                    $entriesByToolName[$toolName]['tags'][] = $tag;
                }
            }
        }

        if ($needle !== null && ! $matchedEndpoint) {
            $this->warn("Endpoint '{$needle}' is not registered via Route::mcp().");
        }

        if ($needle !== null && $matchedEndpoint && ! $matchedApiEnabledEndpoint) {
            $this->warn("Endpoint '{$needle}' is registered, but API endpoint is disabled. Use ->enabledApi().");
        }

        return array_values($entriesByToolName);
    }

    /**
     * @return array<int, array{tool: ToolInterface, tags: array<int, string>, compactEnumExampleCount: int}>
     */
    protected function resolveDiscoveredTools(): array
    {
        $entries = [];
        $seenToolNames = [];

        foreach ($this->discoverToolClasses() as $toolClass) {
            $tool = $this->resolveToolInstance($toolClass);
            if ($tool === null) {
                continue;
            }

            $toolName = $tool->name();
            if (isset($seenToolNames[$toolName])) {
                continue;
            }

            $seenToolNames[$toolName] = true;
            $entries[] = [
                'tool' => $tool,
                'tags' => ['MCP Tools'],
                'compactEnumExampleCount' => Type::defaultCompactEnumExampleCount(),
            ];
        }

        return $entries;
    }

    /**
     * @return array<int, class-string>
     */
    protected function discoverToolClasses(): array
    {
        $classes = [];

        foreach ($this->resolveDiscoverPaths() as $path) {
            if (! File::exists($path) || ! File::isDirectory($path)) {
                continue;
            }

            foreach (File::allFiles($path) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                foreach ($this->extractClassCandidatesFromFile($file->getPathname()) as $classCandidate) {
                    if (! class_exists($classCandidate)) {
                        continue;
                    }

                    if (! is_subclass_of($classCandidate, ToolInterface::class)) {
                        continue;
                    }

                    /** @var class-string<ToolInterface> $classCandidate */
                    $classes[$classCandidate] = $classCandidate;
                }
            }
        }

        return array_values($classes);
    }

    protected function shouldSkipDiscoveryFallback(): bool
    {
        $endpointFilter = $this->option('endpoint');
        if (is_string($endpointFilter) && trim($endpointFilter) !== '') {
            return true;
        }

        /** @var McpEndpointRegistry $registry */
        $registry = app(McpEndpointRegistry::class);

        return $registry->all() !== [];
    }

    protected function matchesEndpoint(McpEndpointDefinition $definition, string $needle): bool
    {
        if ($definition->id === $needle) {
            return true;
        }

        return McpEndpointDefinition::normalizePath($definition->path) === McpEndpointDefinition::normalizePath($needle);
    }

    /**
     * @return array<int, string>
     */
    protected function resolveDiscoverPaths(): array
    {
        $paths = [];

        $defaultPaths = [
            app_path('MCP/Tools'),
            app_path('Tools'),
        ];

        foreach ($defaultPaths as $defaultPath) {
            if (! File::exists($defaultPath) || ! File::isDirectory($defaultPath)) {
                continue;
            }

            $paths[] = $defaultPath;
        }

        /** @var array<int, string|null> $optionPaths */
        $optionPaths = $this->option('discover-path');

        foreach ($optionPaths as $optionPath) {
            if (! is_string($optionPath) || trim($optionPath) === '') {
                continue;
            }

            $chunks = explode(',', $optionPath);
            foreach ($chunks as $chunk) {
                $trimmed = trim($chunk);
                if ($trimmed === '') {
                    continue;
                }

                $paths[] = $this->isAbsolutePath($trimmed) ? $trimmed : base_path($trimmed);
            }
        }

        return array_values(array_unique($paths));
    }

    protected function resolveOutputPath(): string
    {
        $output = $this->option('output');
        if (! is_string($output) || trim($output) === '') {
            return storage_path('api-docs-mcp/api-docs.json');
        }

        $trimmed = trim($output);
        if ($this->isAbsolutePath($trimmed)) {
            return $trimmed;
        }

        return base_path($trimmed);
    }

    protected function resolveTitle(): string
    {
        $title = $this->option('title');

        return is_string($title) && trim($title) !== '' ? trim($title) : 'MCP Tools API';
    }

    protected function resolveVersion(): string
    {
        $version = $this->option('api-version');

        return is_string($version) && trim($version) !== '' ? trim($version) : '1.0.0';
    }

    protected function isAbsolutePath(string $path): bool
    {
        return Str::startsWith($path, ['/']) || (bool) preg_match('/^[A-Za-z]:\\\\/', $path);
    }

    /**
     * @return array<int, string>
     */
    protected function extractClassCandidatesFromFile(string $filePath): array
    {
        $content = File::get($filePath);

        if (! str_contains($content, 'class ') || ! str_contains($content, 'namespace ')) {
            return [];
        }

        preg_match('/^\s*namespace\s+([^;]+);/m', $content, $namespaceMatches);
        $namespace = $namespaceMatches[1] ?? null;
        if (! is_string($namespace) || trim($namespace) === '') {
            return [];
        }

        preg_match_all('/^\s*(?:final\s+|abstract\s+|readonly\s+)*class\s+([A-Za-z_][A-Za-z0-9_]*)\b/m', $content, $classMatches);
        $classNames = $classMatches[1];
        if ($classNames === []) {
            return [];
        }

        $classes = [];
        foreach ($classNames as $className) {
            $classes[] = trim($namespace).'\\'.$className;
        }

        return array_values(array_unique($classes));
    }

    protected function resolveEndpointTag(McpEndpointDefinition $definition): string
    {
        $tag = trim($definition->name);

        return $tag !== '' ? $tag : 'MCP Tools';
    }

    /**
     * @param  class-string  $toolClass
     */
    protected function resolveToolInstance(string $toolClass): ?ToolInterface
    {
        try {
            if (! class_exists($toolClass)) {
                $this->warn("Tool class does not exist: {$toolClass}");

                return null;
            }

            if (! $this->isInstantiableToolClass($toolClass)) {
                $this->warn("Tool class is not instantiable: {$toolClass}");

                return null;
            }

            $tool = App::make($toolClass);
            if (! $tool instanceof ToolInterface) {
                $this->warn("Class does not implement ToolInterface: {$toolClass}");

                return null;
            }

            return $tool;
        } catch (\Throwable $e) {
            $this->warn("Failed to resolve tool {$toolClass}: {$e->getMessage()}");

            return null;
        }
    }

    protected function isInstantiableToolClass(string $toolClass): bool
    {
        try {
            $reflection = new \ReflectionClass($toolClass);

            return $reflection->isInstantiable();
        } catch (\ReflectionException) {
            return false;
        }
    }

    /**
     * @param  array<string, bool>  $used
     */
    protected function uniqueSchemaName(string $toolName, string $suffix, array &$used): string
    {
        $name = Str::studly(preg_replace('/[^A-Za-z0-9]+/', ' ', $toolName) ?: 'Tool').$suffix;
        $candidate = $name;
        $index = 2;

        while (isset($used[$candidate])) {
            $candidate = $name.$index;
            $index++;
        }

        $used[$candidate] = true;

        return $candidate;
    }

    /**
     * @param  array<string, bool>  $used
     */
    protected function uniqueOperationId(string $toolName, array &$used): string
    {
        $base = 'call'.Str::studly(preg_replace('/[^A-Za-z0-9]+/', ' ', $toolName) ?: 'Tool');
        $candidate = $base;
        $index = 2;

        while (isset($used[$candidate])) {
            $candidate = $base.$index;
            $index++;
        }

        $used[$candidate] = true;

        return $candidate;
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalizeInputSchema(array $schema, int $compactEnumExampleCount): array
    {
        $schema = JsonSchemaNormalizer::normalize($schema, $compactEnumExampleCount);

        if ($schema === []) {
            return [
                'type' => 'object',
                'properties' => new stdClass,
                'required' => [],
            ];
        }

        $normalized = $this->normalizeSchema($schema);
        $enriched = $this->applyEnumDefaults($normalized);

        return is_array($enriched) ? $enriched : [];
    }

    protected function normalizeSchema(mixed $value, ?string $key = null): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if ($value === []) {
            if ($key !== null && in_array($key, ['properties', 'patternProperties', '$defs', 'definitions'], true)) {
                return new stdClass;
            }

            return [];
        }

        if (array_is_list($value)) {
            return array_map(fn ($item) => $this->normalizeSchema($item), $value);
        }

        $normalized = [];
        foreach ($value as $childKey => $childValue) {
            $normalized[$childKey] = $this->normalizeSchema($childValue, (string) $childKey);
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<int, array<string, mixed>>
     */
    protected function extractOperationParameters(array $schema): array
    {
        $schemaType = $schema['type'] ?? null;
        if ($schemaType !== 'object') {
            return [];
        }

        $properties = $schema['properties'] ?? null;
        if (! is_array($properties) || array_is_list($properties)) {
            return [];
        }

        $required = $schema['required'] ?? [];
        if (! is_array($required)) {
            $required = [];
        }

        $parameters = [];
        foreach ($properties as $name => $propertySchema) {
            if (! is_string($name) || $name === '') {
                continue;
            }

            if (! is_array($propertySchema)) {
                continue;
            }

            $parameterSchema = $this->buildOperationParameterSchema($propertySchema);
            if ($parameterSchema === null) {
                continue;
            }

            $parameter = [
                'name' => $name,
                'in' => 'query',
                'required' => in_array($name, $required, true),
                'schema' => $parameterSchema,
            ];

            $description = $propertySchema['description'] ?? null;
            if (is_string($description) && trim($description) !== '') {
                $parameter['description'] = trim($description);
            }

            if (array_key_exists('example', $parameterSchema)) {
                $parameter['example'] = $parameterSchema['example'];
            }

            if (($parameterSchema['type'] ?? null) === 'array') {
                $parameter['style'] = 'form';
                $parameter['explode'] = true;
            }

            $parameters[] = $parameter;
        }

        return $parameters;
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    protected function buildOperationParameterSchema(array $schema): ?array
    {
        $normalizedSchema = $this->normalizeSchema($schema);
        if (! is_array($normalizedSchema)) {
            return null;
        }

        $type = $schema['type'] ?? null;
        if (is_string($type) && in_array($type, ['string', 'number', 'integer', 'boolean'], true)) {
            return $normalizedSchema;
        }

        $enum = $schema['enum'] ?? null;
        if (is_array($enum) && $enum !== []) {
            return $normalizedSchema;
        }

        if ($type !== 'array') {
            return null;
        }

        $items = $schema['items'] ?? null;
        if (! is_array($items)) {
            return null;
        }

        $itemType = $items['type'] ?? null;
        $itemEnum = $items['enum'] ?? null;
        if (is_string($itemType) && in_array($itemType, ['string', 'number', 'integer', 'boolean'], true)) {
            return $normalizedSchema;
        }

        if (is_array($itemEnum) && $itemEnum !== []) {
            return $normalizedSchema;
        }

        return null;
    }

    protected function inferEnumType(array $enumValues): ?string
    {
        $nonNullEnumValues = array_values(array_filter($enumValues, fn ($value) => $value !== null));
        if ($nonNullEnumValues === []) {
            return null;
        }

        $allStrings = true;
        $allInts = true;
        $allNumerics = true;
        $allBooleans = true;

        foreach ($nonNullEnumValues as $value) {
            if (! is_string($value)) {
                $allStrings = false;
            }
            if (! is_int($value)) {
                $allInts = false;
            }
            if (! is_int($value) && ! is_float($value)) {
                $allNumerics = false;
            }
            if (! is_bool($value)) {
                $allBooleans = false;
            }
        }

        if ($allStrings) {
            return 'string';
        }
        if ($allInts) {
            return 'integer';
        }
        if ($allNumerics) {
            return 'number';
        }
        if ($allBooleans) {
            return 'boolean';
        }

        return null;
    }

    protected function preferredEnumValue(array $enumValues): mixed
    {
        foreach ($enumValues as $value) {
            if ($value !== null) {
                return $value;
            }
        }

        return $enumValues[0] ?? null;
    }

    protected function applyEnumDefaults(mixed $schema): mixed
    {
        if (! is_array($schema)) {
            return $schema;
        }

        if (array_is_list($schema)) {
            return array_map(fn ($item) => $this->applyEnumDefaults($item), $schema);
        }

        $enriched = [];
        foreach ($schema as $key => $value) {
            $enriched[$key] = $this->applyEnumDefaults($value);
        }

        $enum = $enriched['enum'] ?? null;
        if (is_array($enum) && $enum !== []) {
            $hasNullEnum = in_array(null, $enum, true);

            if (! array_key_exists('type', $enriched)) {
                $inferredType = $this->inferEnumType($enum);
                if (is_string($inferredType)) {
                    $enriched['type'] = $inferredType;
                }
            }

            if ($hasNullEnum && ! array_key_exists('nullable', $enriched)) {
                $enriched['nullable'] = true;
            }

            $preferredValue = $this->preferredEnumValue($enum);
            if (! array_key_exists('default', $enriched)) {
                $enriched['default'] = $preferredValue;
            }

            if (! array_key_exists('example', $enriched)) {
                $enriched['example'] = $preferredValue;
            }
        }

        $type = $enriched['type'] ?? null;
        if ($type === 'string'
            && ! array_key_exists('enum', $enriched)
            && ! array_key_exists('default', $enriched)
            && ! array_key_exists('example', $enriched)) {
            $description = $enriched['description'] ?? null;
            if (is_string($description)) {
                $inferredExample = $this->inferExampleFromDescription($description);
                if ($inferredExample !== null) {
                    $enriched['default'] = $inferredExample;
                    $enriched['example'] = $inferredExample;
                }
            }
        }

        return $enriched;
    }

    protected function inferExampleFromDescription(string $description): ?string
    {
        $patterns = [
            '/\((?:e\.g\.|eg|for example)\s*[:,]?\s*([^)]+)\)/iu',
            '/(?:e\.g\.|eg|for example)\s*[:,]?\s*([A-Za-z0-9_\\-]+(?:\s*,\s*[A-Za-z0-9_\\-]+){0,10})/iu',
            '/examples?\s*[:：]\s*([A-Za-z0-9_\\-]+(?:\s*,\s*[A-Za-z0-9_\\-]+){0,10})/iu',
            '/예\s*[:：]\s*([A-Za-z0-9_\\-]+(?:\s*,\s*[A-Za-z0-9_\\-]+){0,10})/u',
            '/예를\s*들어\s*[:：]?\s*([A-Za-z0-9_\\-]+(?:\s*,\s*[A-Za-z0-9_\\-]+){0,10})/u',
        ];

        foreach ($patterns as $pattern) {
            $matches = [];
            if (! preg_match($pattern, $description, $matches)) {
                continue;
            }

            $candidateGroup = trim($matches[1]);
            if ($candidateGroup === '') {
                continue;
            }

            $candidates = explode(',', $candidateGroup);
            $first = trim($candidates[0]);
            $first = trim($first, " \t\n\r\0\x0B`'\"");
            $first = preg_replace('/[)\].;:]+$/', '', $first);
            if (! is_string($first) || $first === '') {
                continue;
            }

            return $first;
        }

        return null;
    }

    protected function extractToolTitle(ToolInterface $tool): ?string
    {
        $callable = [$tool, 'title'];
        if (! is_callable($callable)) {
            return null;
        }

        $title = call_user_func($callable);
        if (! is_string($title) || trim($title) === '') {
            return null;
        }

        return trim($title);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function extractOutputSchema(ToolInterface $tool): ?array
    {
        $callable = [$tool, 'outputSchema'];
        if (! is_callable($callable)) {
            return null;
        }

        $schema = call_user_func($callable);
        if (! is_array($schema)) {
            return null;
        }

        return $schema;
    }
}
