<?php

namespace OPGG\LaravelMcpServer\Services\SwaggerParser;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SwaggerParser
{
    protected array $spec;

    protected string $version;

    protected ?string $baseUrl = null;

    protected array $securitySchemes = [];

    protected array $endpoints = [];

    /**
     * Load Swagger/OpenAPI spec from URL or file
     */
    public function load(string $source): self
    {
        if (Str::startsWith($source, ['http://', 'https://'])) {
            $this->loadFromUrl($source);
        } else {
            $this->loadFromFile($source);
        }

        $this->detectVersion();
        $this->parseSpec();

        return $this;
    }

    /**
     * Load spec from URL
     */
    protected function loadFromUrl(string $url): void
    {
        $response = Http::get($url);

        if (! $response->successful()) {
            throw new \Exception("Failed to load Swagger spec from URL: {$url}");
        }

        $contentType = $response->header('Content-Type');

        if (Str::contains($contentType, 'yaml') || Str::endsWith($url, ['.yaml', '.yml'])) {
            // For YAML support, we'll need symfony/yaml package
            throw new \Exception('YAML format not yet supported. Please use JSON format.');
        }

        $this->spec = $response->json();

        if (! is_array($this->spec)) {
            throw new \Exception('Invalid Swagger/OpenAPI spec format');
        }
    }

    /**
     * Load spec from file
     */
    protected function loadFromFile(string $path): void
    {
        if (! file_exists($path)) {
            throw new \Exception("Swagger spec file not found: {$path}");
        }

        $content = file_get_contents($path);

        if (Str::endsWith($path, ['.yaml', '.yml'])) {
            // For YAML support, we'll need symfony/yaml package
            throw new \Exception('YAML format not yet supported. Please use JSON format.');
        }

        $this->spec = json_decode($content, true);

        if (! is_array($this->spec)) {
            throw new \Exception('Invalid Swagger/OpenAPI spec format');
        }
    }

    /**
     * Detect Swagger/OpenAPI version
     */
    protected function detectVersion(): void
    {
        if (isset($this->spec['openapi'])) {
            $this->version = 'openapi-'.$this->spec['openapi'];
        } elseif (isset($this->spec['swagger'])) {
            $this->version = 'swagger-'.$this->spec['swagger'];
        } else {
            throw new \Exception('Could not detect Swagger/OpenAPI version');
        }
    }

    /**
     * Parse the spec to extract relevant information
     */
    protected function parseSpec(): void
    {
        // Extract base URL
        $this->extractBaseUrl();

        // Extract security schemes
        $this->extractSecuritySchemes();

        // Extract endpoints
        $this->extractEndpoints();
    }

    /**
     * Extract base URL from spec
     */
    protected function extractBaseUrl(): void
    {
        if (Str::startsWith($this->version, 'openapi-')) {
            // OpenAPI 3.x
            if (isset($this->spec['servers'][0]['url'])) {
                $this->baseUrl = $this->spec['servers'][0]['url'];
            }
        } else {
            // Swagger 2.0
            $scheme = $this->spec['schemes'][0] ?? 'https';
            $host = $this->spec['host'] ?? '';
            $basePath = $this->spec['basePath'] ?? '';

            if ($host) {
                $this->baseUrl = "{$scheme}://{$host}{$basePath}";
            }
        }
    }

    /**
     * Extract security schemes
     */
    protected function extractSecuritySchemes(): void
    {
        if (Str::startsWith($this->version, 'openapi-')) {
            // OpenAPI 3.x
            $this->securitySchemes = $this->spec['components']['securitySchemes'] ?? [];
        } else {
            // Swagger 2.0
            $this->securitySchemes = $this->spec['securityDefinitions'] ?? [];
        }
    }

    /**
     * Extract endpoints from paths
     */
    protected function extractEndpoints(): void
    {
        $paths = $this->spec['paths'] ?? [];

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                // Skip non-HTTP methods
                if (! in_array($method, ['get', 'post', 'put', 'patch', 'delete', 'head', 'options'])) {
                    continue;
                }

                $endpoint = [
                    'path' => $path,
                    'method' => strtoupper($method),
                    'operationId' => $operation['operationId'] ?? null,
                    'summary' => $operation['summary'] ?? '',
                    'description' => $operation['description'] ?? '',
                    'tags' => $operation['tags'] ?? [],
                    'deprecated' => $operation['deprecated'] ?? false,
                    'parameters' => $this->extractParameters($operation, $path),
                    'requestBody' => $this->extractRequestBody($operation),
                    'responses' => $this->extractResponses($operation),
                    'security' => $operation['security'] ?? $this->spec['security'] ?? [],
                ];

                $this->endpoints[] = $endpoint;
            }
        }
    }

    /**
     * Extract parameters from operation
     */
    protected function extractParameters(array $operation, string $path): array
    {
        $parameters = [];

        // Get path-level parameters if any
        $pathItem = $this->spec['paths'][$path] ?? [];
        $pathParams = $pathItem['parameters'] ?? [];

        // Get operation-level parameters
        $operationParams = $operation['parameters'] ?? [];

        // Merge parameters (operation overrides path)
        $allParams = array_merge($pathParams, $operationParams);

        foreach ($allParams as $param) {
            // Handle $ref
            if (isset($param['$ref'])) {
                $param = $this->resolveReference($param['$ref']);
            }

            $parameters[] = [
                'name' => $param['name'] ?? '',
                'in' => $param['in'] ?? 'query', // path, query, header, cookie
                'description' => $param['description'] ?? '',
                'required' => $param['required'] ?? false,
                'schema' => $param['schema'] ?? $param, // OpenAPI 3.x vs Swagger 2.0
                'type' => $param['type'] ?? $param['schema']['type'] ?? 'string',
            ];
        }

        return $parameters;
    }

    /**
     * Extract request body (OpenAPI 3.x)
     */
    protected function extractRequestBody(array $operation): ?array
    {
        if (! isset($operation['requestBody'])) {
            // Check for Swagger 2.0 body parameters
            foreach ($operation['parameters'] ?? [] as $param) {
                if (($param['in'] ?? '') === 'body') {
                    return [
                        'required' => $param['required'] ?? false,
                        'schema' => $param['schema'] ?? [],
                    ];
                }
            }

            return null;
        }

        $requestBody = $operation['requestBody'];

        // Handle $ref
        if (isset($requestBody['$ref'])) {
            $requestBody = $this->resolveReference($requestBody['$ref']);
        }

        return [
            'required' => $requestBody['required'] ?? false,
            'content' => $requestBody['content'] ?? [],
            'description' => $requestBody['description'] ?? '',
        ];
    }

    /**
     * Extract responses
     */
    protected function extractResponses(array $operation): array
    {
        $responses = [];

        foreach ($operation['responses'] ?? [] as $code => $response) {
            // Handle $ref
            if (isset($response['$ref'])) {
                $response = $this->resolveReference($response['$ref']);
            }

            $responses[$code] = [
                'description' => $response['description'] ?? '',
                'content' => $response['content'] ?? [],
                'schema' => $response['schema'] ?? null, // Swagger 2.0
            ];
        }

        return $responses;
    }

    /**
     * Resolve $ref references
     */
    protected function resolveReference(string $ref): array
    {
        // Remove '#/' prefix
        $ref = str_replace('#/', '', $ref);

        // Split by /
        $parts = explode('/', $ref);

        // Navigate through spec
        $resolved = $this->spec;
        foreach ($parts as $part) {
            $resolved = $resolved[$part] ?? [];
        }

        return $resolved;
    }

    /**
     * Get all endpoints
     */
    public function getEndpoints(): array
    {
        return $this->endpoints;
    }

    /**
     * Get endpoints grouped by tag
     */
    public function getEndpointsByTag(): array
    {
        $grouped = [];

        foreach ($this->endpoints as $endpoint) {
            $tags = $endpoint['tags'] ?: ['default'];

            foreach ($tags as $tag) {
                if (! isset($grouped[$tag])) {
                    $grouped[$tag] = [];
                }
                $grouped[$tag][] = $endpoint;
            }
        }

        return $grouped;
    }

    /**
     * Get spec info
     */
    public function getInfo(): array
    {
        return [
            'version' => $this->version,
            'title' => $this->spec['info']['title'] ?? 'Unknown',
            'description' => $this->spec['info']['description'] ?? '',
            'baseUrl' => $this->baseUrl,
            'securitySchemes' => array_keys($this->securitySchemes),
            'totalEndpoints' => count($this->endpoints),
            'tags' => $this->getTags(),
        ];
    }

    /**
     * Get all tags
     */
    public function getTags(): array
    {
        $tags = [];

        foreach ($this->endpoints as $endpoint) {
            foreach ($endpoint['tags'] as $tag) {
                $tags[$tag] = true;
            }
        }

        return array_keys($tags);
    }

    /**
     * Get security schemes
     */
    public function getSecuritySchemes(): array
    {
        return $this->securitySchemes;
    }

    /**
     * Get base URL
     */
    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    /**
     * Set base URL
     */
    public function setBaseUrl(string $url): self
    {
        $this->baseUrl = rtrim($url, '/');

        return $this;
    }
}
