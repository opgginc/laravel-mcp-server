<?php

namespace OPGG\LaravelMcpServer\Services\SwaggerParser;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SwaggerParser
{
    protected array $spec;

    protected string $version;

    protected ?string $baseUrl = null;

    protected ?string $sourceUrl = null;

    protected ?string $originalServerUrl = null;

    protected array $securitySchemes = [];

    protected array $endpoints = [];

    /**
     * Validate URL to prevent SSRF attacks
     */
    protected function isValidUrl(string $source): bool
    {
        // Basic URL validation
        if (! filter_var($source, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parsedUrl = parse_url($source);

        // Ensure scheme is HTTP or HTTPS only
        if (! in_array($parsedUrl['scheme'] ?? '', ['http', 'https'])) {
            return false;
        }

        // Additional SSRF protections could be added here:
        // - Block private IP ranges (127.0.0.1, 10.0.0.0/8, etc.)
        // - Block localhost domains
        // - Whitelist allowed domains
        // For now, we allow all HTTP/HTTPS URLs but with proper validation

        return true;
    }

    /**
     * Load Swagger/OpenAPI spec from URL or file
     */
    public function load(string $source): self
    {
        if ($this->isValidUrl($source)) {
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
        // Store the source URL for later use
        $this->sourceUrl = $url;

        $response = Http::get($url);

        if (! $response->successful()) {
            throw new \Exception("Failed to load Swagger spec from URL: {$url}");
        }

        $contentType = $response->header('Content-Type');

        if (Str::contains($contentType, 'yaml') || Str::endsWith($url, ['.yaml', '.yml'])) {
            // For YAML support, we'll need symfony/yaml package
            throw new \Exception('YAML format not yet supported. Please use JSON format.');
        }

        $decodedSpec = $response->json();
        if (! is_array($decodedSpec)) {
            throw new \Exception('Invalid Swagger/OpenAPI spec format');
        }

        $this->spec = $decodedSpec;
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

        $decodedSpec = json_decode($content, true);
        if (! is_array($decodedSpec)) {
            throw new \Exception('Invalid Swagger/OpenAPI spec format');
        }

        $this->spec = $decodedSpec;
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
                $serverUrl = $this->spec['servers'][0]['url'];

                // Store original server URL for debugging
                $this->originalServerUrl = $serverUrl;

                // Check if the server URL is relative (doesn't start with http:// or https://)
                if (! preg_match('/^https?:\/\//', $serverUrl)) {
                    // If we have a source URL, extract its domain
                    if ($this->sourceUrl) {
                        $parsedUrl = parse_url($this->sourceUrl);
                        $scheme = $parsedUrl['scheme'] ?? 'https';
                        $host = $parsedUrl['host'] ?? '';
                        $port = isset($parsedUrl['port']) ? ":{$parsedUrl['port']}" : '';

                        if ($host) {
                            // Build the base URL using the source domain
                            $baseHost = "{$scheme}://{$host}{$port}";

                            // If server URL starts with /, it's absolute from root
                            if (str_starts_with($serverUrl, '/')) {
                                $this->baseUrl = $baseHost.$serverUrl;
                            } else {
                                // Otherwise, it's relative to the spec file location
                                $this->baseUrl = $baseHost.'/'.ltrim($serverUrl, '/');
                            }
                        } else {
                            // Fallback to the server URL as-is
                            $this->baseUrl = $serverUrl;
                        }
                    } else {
                        // No source URL available, use as-is
                        $this->baseUrl = $serverUrl;
                    }
                } else {
                    // Server URL is already absolute
                    $this->baseUrl = $serverUrl;
                }
            }
        } else {
            // Swagger 2.0
            $scheme = $this->spec['schemes'][0] ?? 'https';
            $host = $this->spec['host'] ?? '';
            $basePath = $this->spec['basePath'] ?? '';

            if ($host) {
                $this->baseUrl = "{$scheme}://{$host}{$basePath}";
            } elseif ($this->sourceUrl) {
                // No host specified in spec, try to use source URL's host
                $parsedUrl = parse_url($this->sourceUrl);
                $sourceScheme = $parsedUrl['scheme'] ?? $scheme;
                $sourceHost = $parsedUrl['host'] ?? '';
                $port = isset($parsedUrl['port']) ? ":{$parsedUrl['port']}" : '';

                if ($sourceHost) {
                    $this->baseUrl = "{$sourceScheme}://{$sourceHost}{$port}{$basePath}";
                }
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
            'originalServerUrl' => $this->originalServerUrl,
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
