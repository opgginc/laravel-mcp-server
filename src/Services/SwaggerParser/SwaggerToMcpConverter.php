<?php

namespace OPGG\LaravelMcpServer\Services\SwaggerParser;

use Illuminate\Support\Str;

class SwaggerToMcpConverter
{
    protected SwaggerParser $parser;

    protected array $authConfig = [];

    public function __construct(SwaggerParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Set authentication configuration
     */
    public function setAuthConfig(array $config): self
    {
        $this->authConfig = $config;

        return $this;
    }

    /**
     * Convert endpoint to MCP tool parameters
     */
    public function convertEndpointToTool(array $endpoint, string $className): array
    {
        $toolName = $this->generateToolName($endpoint);
        $description = $this->generateDescription($endpoint);
        $inputSchema = $this->generateInputSchema($endpoint);
        $annotations = $this->generateAnnotations($endpoint);
        $executeLogic = $this->generateExecuteLogic($endpoint);
        $imports = $this->generateImports($endpoint);

        return [
            'className' => $className,
            'toolName' => $toolName,
            'description' => $description,
            'inputSchema' => $inputSchema,
            'annotations' => $annotations,
            'executeLogic' => $executeLogic,
            'imports' => $imports,
        ];
    }

    /**
     * Generate tool name from endpoint
     */
    protected function generateToolName(array $endpoint): string
    {
        // Check if operationId is a hash (32 char hex string)
        $useOperationId = ! empty($endpoint['operationId'])
            && ! preg_match('/^[a-f0-9]{32}$/i', $endpoint['operationId']);

        if ($useOperationId) {
            return Str::kebab($endpoint['operationId']);
        }

        // Generate from method and path
        $method = strtolower($endpoint['method']);
        $path = $this->convertPathToKebab($endpoint['path']);

        return "{$method}-{$path}";
    }

    /**
     * Convert API path to kebab-case name
     * Example: /lol/{region}/server-stats -> lol-region-server-stats
     */
    protected function convertPathToKebab(string $path): string
    {
        // Remove leading/trailing slashes
        $path = trim($path, '/');

        // Replace path parameters {param} with just param
        $path = preg_replace('/\{([^}]+)\}/', '$1', $path);

        // Replace forward slashes with hyphens
        $path = str_replace('/', '-', $path);

        // Convert to kebab case if needed (handles camelCase and PascalCase)
        $path = Str::kebab($path);

        // Remove any double hyphens
        $path = preg_replace('/-+/', '-', $path);

        return $path;
    }

    /**
     * Generate description
     */
    protected function generateDescription(array $endpoint): string
    {
        $description = $endpoint['summary'] ?: $endpoint['description'];

        if (! $description) {
            $description = "{$endpoint['method']} {$endpoint['path']}";
        }

        // Add endpoint info
        $description .= " [API: {$endpoint['method']} {$endpoint['path']}]";

        return addslashes($description);
    }

    /**
     * Generate input schema
     */
    protected function generateInputSchema(array $endpoint): array
    {
        $properties = [];
        $required = [];

        // Process parameters
        foreach ($endpoint['parameters'] as $param) {
            $propName = $param['name'];

            $properties[$propName] = [
                'type' => $this->mapSwaggerTypeToJsonSchema($param['type']),
                'description' => $param['description']." (in: {$param['in']})",
            ];

            if ($param['required']) {
                $required[] = $propName;
            }

            // Add schema constraints if available
            if (isset($param['schema'])) {
                $this->addSchemaConstraints($properties[$propName], $param['schema']);
            }
        }

        // Process request body
        if ($endpoint['requestBody']) {
            // For simplicity, we'll create a 'body' parameter
            $properties['body'] = [
                'type' => 'object',
                'description' => $endpoint['requestBody']['description'] ?? 'Request body',
            ];

            if ($endpoint['requestBody']['required']) {
                $required[] = 'body';
            }

            // Try to extract schema from content
            if (! empty($endpoint['requestBody']['content']['application/json']['schema'])) {
                $schema = $endpoint['requestBody']['content']['application/json']['schema'];
                if (isset($schema['properties'])) {
                    $properties['body']['properties'] = $this->convertSchemaProperties($schema['properties']);
                    if (isset($schema['required'])) {
                        $properties['body']['required'] = $schema['required'];
                    }
                }
            }
        }

        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => $required,
        ];
    }

    /**
     * Convert schema properties
     */
    protected function convertSchemaProperties(array $properties): array
    {
        $converted = [];

        foreach ($properties as $name => $prop) {
            $converted[$name] = [
                'type' => $prop['type'] ?? 'string',
                'description' => $prop['description'] ?? '',
            ];

            if (isset($prop['enum'])) {
                $converted[$name]['enum'] = $prop['enum'];
            }

            if (isset($prop['default'])) {
                $converted[$name]['default'] = $prop['default'];
            }
        }

        return $converted;
    }

    /**
     * Map Swagger type to JSON Schema type
     */
    protected function mapSwaggerTypeToJsonSchema(string $type): string
    {
        $mapping = [
            'integer' => 'integer',
            'number' => 'number',
            'string' => 'string',
            'boolean' => 'boolean',
            'array' => 'array',
            'object' => 'object',
            'file' => 'string', // File uploads as string (path or base64)
        ];

        return $mapping[$type] ?? 'string';
    }

    /**
     * Add schema constraints
     */
    protected function addSchemaConstraints(array &$property, array $schema): void
    {
        if (isset($schema['enum'])) {
            $property['enum'] = $schema['enum'];
        }

        if (isset($schema['minimum'])) {
            $property['minimum'] = $schema['minimum'];
        }

        if (isset($schema['maximum'])) {
            $property['maximum'] = $schema['maximum'];
        }

        if (isset($schema['minLength'])) {
            $property['minLength'] = $schema['minLength'];
        }

        if (isset($schema['maxLength'])) {
            $property['maxLength'] = $schema['maxLength'];
        }

        if (isset($schema['pattern'])) {
            $property['pattern'] = $schema['pattern'];
        }

        if (isset($schema['default'])) {
            $property['default'] = $schema['default'];
        }
    }

    /**
     * Generate annotations
     */
    protected function generateAnnotations(array $endpoint): array
    {
        $method = strtoupper($endpoint['method']);
        $isReadOnly = in_array($method, ['GET', 'HEAD', 'OPTIONS']);

        return [
            'title' => $endpoint['summary'] ?: "{$method} {$endpoint['path']}",
            'readOnlyHint' => $isReadOnly,
            'destructiveHint' => $method === 'DELETE',
            'idempotentHint' => in_array($method, ['GET', 'PUT', 'DELETE', 'HEAD', 'OPTIONS']),
            'openWorldHint' => true, // External API call
            'deprecated' => $endpoint['deprecated'] ?? false,
        ];
    }

    /**
     * Generate execute logic
     */
    protected function generateExecuteLogic(array $endpoint): string
    {
        $method = strtolower($endpoint['method']);
        $path = $endpoint['path'];

        $logic = <<<'PHP'
        // Validate input parameters
        $validator = Validator::make($arguments, [
            // Add validation rules based on schema
        ]);

        if ($validator->fails()) {
            throw new JsonRpcErrorException(
                message: 'Validation failed: ' . $validator->errors()->first(),
                code: JsonRpcErrorCode::INVALID_REQUEST
            );
        }

PHP;

        // Build URL with path parameters
        $logic .= $this->generateUrlBuilder($path, $endpoint['parameters']);

        // Add authentication
        $logic .= $this->generateAuthLogic($endpoint);

        // Build request
        $logic .= $this->generateHttpRequest($method, $endpoint);

        // Handle response
        $logic .= <<<'PHP'

        // Check response status
        if (!$response->successful()) {
            throw new JsonRpcErrorException(
                message: 'API request failed: ' . $response->body(),
                code: JsonRpcErrorCode::INTERNAL_ERROR
            );
        }

        // Return response data
        return [
            'success' => true,
            'data' => $response->json(),
            'status' => $response->status(),
        ];
PHP;

        return $logic;
    }

    /**
     * Generate URL builder code
     */
    protected function generateUrlBuilder(string $path, array $parameters): string
    {
        $baseUrl = $this->parser->getBaseUrl() ?: 'https://api.example.com';

        $code = "        // Build URL\n";
        $code .= "        \$url = '{$baseUrl}{$path}';\n";

        // Replace path parameters
        $pathParams = array_filter($parameters, fn ($p) => $p['in'] === 'path');
        foreach ($pathParams as $param) {
            $name = $param['name'];
            $code .= "        \$url = str_replace('{{$name}}', \$arguments['{$name}'] ?? '', \$url);\n";
        }

        $code .= "\n";

        return $code;
    }

    /**
     * Generate authentication logic
     */
    protected function generateAuthLogic(array $endpoint): string
    {
        if (empty($endpoint['security']) && empty($this->authConfig)) {
            return '';
        }

        $code = "        // Authentication\n";
        $code .= "        \$headers = [];\n";

        // Simple bearer token example
        if (! empty($this->authConfig['bearer_token'])) {
            $code .= "        \$headers['Authorization'] = 'Bearer ' . config('services.api.token');\n";
        }

        // API Key example
        if (! empty($this->authConfig['api_key'])) {
            $location = $this->authConfig['api_key']['location'] ?? 'header';
            $name = $this->authConfig['api_key']['name'] ?? 'X-API-Key';

            if ($location === 'header') {
                $code .= "        \$headers['{$name}'] = config('services.api.key');\n";
            }
        }

        $code .= "\n";

        return $code;
    }

    /**
     * Generate HTTP request code
     */
    protected function generateHttpRequest(string $method, array $endpoint): string
    {
        $code = "        // Build request\n";
        $code .= "        \$request = Http::withHeaders(\$headers ?? [])\n";
        $code .= "            ->timeout(30)\n";
        $code .= "            ->retry(3, 100);\n\n";

        // Add query parameters
        $queryParams = array_filter($endpoint['parameters'], fn ($p) => $p['in'] === 'query');
        if (! empty($queryParams)) {
            $code .= "        // Add query parameters\n";
            $code .= "        \$queryParams = [];\n";
            foreach ($queryParams as $param) {
                $name = $param['name'];
                $code .= "        if (isset(\$arguments['{$name}'])) {\n";
                $code .= "            \$queryParams['{$name}'] = \$arguments['{$name}'];\n";
                $code .= "        }\n";
            }
            $code .= "        if (!empty(\$queryParams)) {\n";
            $code .= "            \$request = \$request->withQueryParameters(\$queryParams);\n";
            $code .= "        }\n\n";
        }

        // Make request
        $code .= "        // Execute request\n";

        switch ($method) {
            case 'get':
                $code .= "        \$response = \$request->get(\$url);\n";
                break;
            case 'post':
                $code .= "        \$response = \$request->post(\$url, \$arguments['body'] ?? []);\n";
                break;
            case 'put':
                $code .= "        \$response = \$request->put(\$url, \$arguments['body'] ?? []);\n";
                break;
            case 'patch':
                $code .= "        \$response = \$request->patch(\$url, \$arguments['body'] ?? []);\n";
                break;
            case 'delete':
                $code .= "        \$response = \$request->delete(\$url);\n";
                break;
            default:
                $code .= "        \$response = \$request->{$method}(\$url);\n";
        }

        return $code;
    }

    /**
     * Generate imports
     */
    protected function generateImports(array $endpoint): array
    {
        // $endpoint parameter is for future extensibility (e.g., different imports based on endpoint type)
        return [
            'Illuminate\\Support\\Facades\\Http',
        ];
    }

    /**
     * Generate class name from endpoint
     */
    public function generateClassName(array $endpoint, ?string $prefix = null): string
    {
        // Check if operationId is a hash (32 char hex string)
        $useOperationId = ! empty($endpoint['operationId'])
            && ! preg_match('/^[a-f0-9]{32}$/i', $endpoint['operationId']);

        if ($useOperationId) {
            $name = Str::studly($endpoint['operationId']);
        } else {
            // Generate from method and path
            $method = ucfirst(strtolower($endpoint['method']));
            $pathName = $this->convertPathToStudly($endpoint['path']);
            $name = "{$method}{$pathName}";
        }

        if ($prefix) {
            $name = "{$prefix}{$name}";
        }

        // Ensure it ends with Tool
        if (! Str::endsWith($name, 'Tool')) {
            $name .= 'Tool';
        }

        return $name;
    }

    /**
     * Convert API path to StudlyCase name
     * Example: /lol/{region}/server-stats -> LolRegionServerStats
     */
    protected function convertPathToStudly(string $path): string
    {
        // Remove leading/trailing slashes
        $path = trim($path, '/');

        // Split by forward slashes
        $segments = explode('/', $path);

        // Process each segment
        $processed = [];
        foreach ($segments as $segment) {
            // Remove curly braces from parameters
            $segment = str_replace(['{', '}'], '', $segment);

            // Convert each segment to StudlyCase
            // This handles kebab-case (server-stats), snake_case, and camelCase
            $processed[] = Str::studly($segment);
        }

        // Join all segments
        return implode('', $processed);
    }

    /**
     * Generate resource class name from endpoint
     */
    public function generateResourceClassName(array $endpoint, ?string $prefix = null): string
    {
        // Check if operationId is a hash (32 char hex string)
        $useOperationId = ! empty($endpoint['operationId'])
            && ! preg_match('/^[a-f0-9]{32}$/i', $endpoint['operationId']);

        if ($useOperationId) {
            $name = Str::studly($endpoint['operationId']);
        } else {
            // Generate from path only (no method prefix for resources)
            $pathName = $this->convertPathToStudly($endpoint['path']);
            $name = $pathName;
        }

        if ($prefix) {
            $name = "{$prefix}{$name}";
        }

        // Ensure it ends with Resource
        if (! Str::endsWith($name, 'Resource')) {
            $name .= 'Resource';
        }

        return $name;
    }

    /**
     * Convert endpoint to MCP resource parameters
     */
    public function convertEndpointToResource(array $endpoint, string $className): array
    {
        $uri = $this->generateResourceUri($endpoint);
        $name = $this->generateResourceName($endpoint);
        $description = $this->generateResourceDescription($endpoint);
        $readLogic = $this->generateResourceReadLogic($endpoint);

        return [
            'className' => $className,
            'uri' => $uri,
            'name' => $name,
            'description' => $description,
            'mimeType' => 'application/json',
            'readLogic' => $readLogic,
        ];
    }

    /**
     * Generate resource URI from endpoint
     */
    protected function generateResourceUri(array $endpoint): string
    {
        // Convert API path to a resource URI
        // Example: /api/users/{id} -> api://users/{id}
        $path = trim($endpoint['path'], '/');

        // Remove common API prefixes
        $path = preg_replace('/^api\//', '', $path);

        return "api://{$path}";
    }

    /**
     * Generate resource name from endpoint
     */
    protected function generateResourceName(array $endpoint): string
    {
        if (! empty($endpoint['summary'])) {
            return $endpoint['summary'];
        }

        // Generate from path
        $path = trim($endpoint['path'], '/');
        $path = str_replace(['{', '}'], '', $path);
        $parts = explode('/', $path);

        return ucfirst(end($parts)).' Data';
    }

    /**
     * Generate resource description from endpoint
     */
    protected function generateResourceDescription(array $endpoint): string
    {
        $description = $endpoint['description'] ?: $endpoint['summary'];

        if (! $description) {
            $description = "Resource for {$endpoint['path']} endpoint";
        }

        $description .= " [API: GET {$endpoint['path']}]";

        // Add parameter info
        if (! empty($endpoint['parameters'])) {
            $params = array_map(fn ($p) => $p['name'], $endpoint['parameters']);
            $description .= ' Parameters: '.implode(', ', $params);
        }

        return addslashes($description);
    }

    /**
     * Generate resource read logic
     */
    protected function generateResourceReadLogic(array $endpoint): string
    {
        $baseUrl = $this->parser->getBaseUrl() ?: 'https://api.example.com';
        $path = $endpoint['path'];

        $logic = <<<'PHP'
        try {
            // Build URL
            $url = '{{ baseUrl }}{{ path }}';
            
            // Replace path parameters if provided
            // Note: In a real resource, you'd get these from the URI or context
{{ pathParams }}
            
            // Prepare headers
            $headers = [];
{{ authHeaders }}
            
            // Make HTTP request
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->retry(3, 100)
{{ queryParams }}
                ->get($url);
            
            if (!$response->successful()) {
                throw new \Exception("API request failed: Status {$response->status()}");
            }
            
            return [
                'uri' => $this->uri,
                'mimeType' => 'application/json',
                'text' => $response->body(),
            ];
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Failed to read resource {$this->uri}: " . $e->getMessage()
            );
        }
PHP;

        // Replace placeholders
        $logic = str_replace('{{ baseUrl }}', $baseUrl, $logic);
        $logic = str_replace('{{ path }}', $path, $logic);

        // Add path parameter replacements
        $pathParams = $this->generateResourcePathParams($endpoint['parameters'] ?? []);
        $logic = str_replace('{{ pathParams }}', $pathParams, $logic);

        // Add authentication headers
        $authHeaders = $this->generateResourceAuthHeaders($endpoint);
        $logic = str_replace('{{ authHeaders }}', $authHeaders, $logic);

        // Add query parameters
        $queryParams = $this->generateResourceQueryParams($endpoint['parameters'] ?? []);
        $logic = str_replace('{{ queryParams }}', $queryParams, $logic);

        return $logic;
    }

    /**
     * Generate path parameter replacements for resource
     */
    protected function generateResourcePathParams(array $parameters): string
    {
        $pathParams = array_filter($parameters, fn ($p) => $p['in'] === 'path');

        if (empty($pathParams)) {
            return '';
        }

        $code = '';
        foreach ($pathParams as $param) {
            $name = $param['name'];
            $code .= "            // TODO: Implement logic to get '{$name}' value\n";
            $code .= "            // \$url = str_replace('{{$name}}', \$valueFor".Str::studly($name).", \$url);\n";
        }

        return rtrim($code);
    }

    /**
     * Generate authentication headers for resource
     */
    protected function generateResourceAuthHeaders(array $endpoint): string
    {
        if (empty($endpoint['security']) && empty($this->authConfig)) {
            return '';
        }

        $code = '';

        if (! empty($this->authConfig['bearer_token'])) {
            $code .= "            \$headers['Authorization'] = 'Bearer ' . config('services.api.token');\n";
        }

        if (! empty($this->authConfig['api_key'])) {
            $name = $this->authConfig['api_key']['name'] ?? 'X-API-Key';
            $code .= "            \$headers['{$name}'] = config('services.api.key');\n";
        }

        return rtrim($code);
    }

    /**
     * Generate query parameters for resource
     */
    protected function generateResourceQueryParams(array $parameters): string
    {
        $queryParams = array_filter($parameters, fn ($p) => $p['in'] === 'query');

        if (empty($queryParams)) {
            return '';
        }

        $code = "                ->withQueryParameters([\n";
        foreach ($queryParams as $param) {
            $name = $param['name'];
            $required = $param['required'] ? 'required' : 'optional';
            $code .= "                    // '{$name}' => \$valueFor".Str::studly($name).", // {$required}\n";
        }
        $code .= "                ])\n";

        return $code;
    }
}
