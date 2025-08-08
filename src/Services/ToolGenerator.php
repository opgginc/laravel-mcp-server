<?php

namespace OPGG\LaravelMcpServer\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ToolGenerator
{
    /**
     * Generate MCP tool from endpoint specification
     */
    public function generateTool(
        array $endpoint,
        string $outputDir,
        string $baseUrlEnv,
        bool $force = false
    ): ?string {
        $className = $this->generateClassName($endpoint);
        $filePath = "{$outputDir}/{$className}.php";

        // Check if file exists
        if (File::exists($filePath) && ! $force) {
            if (! $this->confirmOverwrite($className)) {
                return null;
            }
        }

        // Generate tool content
        $content = $this->generateToolContent($endpoint, $className, $baseUrlEnv, $outputDir);

        // Write file
        File::put($filePath, $content);

        // Return fully qualified class name
        $namespace = $this->getNamespaceFromPath($outputDir);

        return "{$namespace}\\{$className}";
    }

    /**
     * Generate class name from endpoint
     */
    protected function generateClassName(array $endpoint): string
    {
        $method = ucfirst(strtolower($endpoint['method']));
        $path = str_replace(['{', '}', '/', '-'], ['', '', '', ''], $endpoint['path']);

        // Convert to PascalCase
        $parts = explode('/', trim($path, '/'));
        $parts = array_map(function ($part) {
            return Str::studly($part);
        }, $parts);

        return $method.implode('', $parts).'Tool';
    }

    /**
     * Generate tool name (kebab-case)
     */
    protected function generateToolName(array $endpoint): string
    {
        $method = strtolower($endpoint['method']);
        $path = str_replace(['{', '}', '/'], ['', '', '-'], $endpoint['path']);
        $path = trim($path, '-');

        return "{$method}-{$path}";
    }

    /**
     * Get namespace from directory path
     */
    protected function getNamespaceFromPath(string $path): string
    {
        // Remove base_path and any leading/trailing slashes
        $relativePath = trim(str_replace(base_path(), '', $path), '/');

        // Split into parts and convert to namespace
        if (empty($relativePath)) {
            return '';
        }

        $parts = explode('/', $relativePath);

        // Convert to namespace
        $namespace = array_map(function ($part) {
            return Str::studly($part);
        }, $parts);

        return implode('\\', $namespace);
    }

    /**
     * Confirm file overwrite
     */
    protected function confirmOverwrite(string $className): bool
    {
        // In non-interactive mode, skip by default
        if (! app()->runningInConsole()) {
            return false;
        }

        $response = readline("File {$className}.php already exists. Overwrite? (y/n): ");

        return strtolower($response) === 'y';
    }

    /**
     * Generate tool file content
     */
    protected function generateToolContent(array $endpoint, string $className, string $baseUrlEnv, string $outputDir): string
    {
        // Use dynamic namespace based on output directory
        $namespace = $this->getNamespaceFromPath($outputDir);
        if (empty($namespace)) {
            $namespace = 'App\\MCP\\Tools\\Swagger';
        }
        $toolName = $this->generateToolName($endpoint);
        $inputSchema = $this->generateInputSchema($endpoint);
        $executeMethod = $this->generateExecuteMethod($endpoint, $baseUrlEnv);

        return <<<PHP
<?php

namespace {$namespace};

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

/**
 * MCP Tool generated from Swagger/OpenAPI specification
 * 
 * Endpoint: {$endpoint['method']} {$endpoint['path']}
 * {$endpoint['summary']}
 */
class {$className} implements ToolInterface
{
    public function name(): string
    {
        return '{$toolName}';
    }

    public function description(): string
    {
        return '{$this->escapeString($endpoint['description'] ?: $endpoint['summary'])}';
    }

    public function inputSchema(): array
    {
        return {$inputSchema};
    }

    public function execute(array \$input): array|string
    {
{$executeMethod}
    }

    public function annotations(): array
    {
        return [];
    }

    public function messageType(): string
    {
        return 'tool_call';
    }

    protected function escapeString(string \$str): string
    {
        return str_replace("'", "\\'", \$str);
    }
}
PHP;
    }

    /**
     * Escape string for PHP output
     */
    protected function escapeString(string $str): string
    {
        return str_replace("'", "\\'", $str);
    }

    /**
     * Generate input schema
     */
    protected function generateInputSchema(array $endpoint): string
    {
        $properties = [];
        $required = [];

        // Add path parameters
        foreach ($endpoint['parameters'] as $param) {
            if ($param['in'] === 'path') {
                $properties[$param['name']] = array_merge(
                    $param['schema'],
                    ['description' => $param['description']]
                );
                if ($param['required']) {
                    $required[] = $param['name'];
                }
            }
        }

        // Add query parameters
        foreach ($endpoint['parameters'] as $param) {
            if ($param['in'] === 'query') {
                $properties[$param['name']] = array_merge(
                    $param['schema'],
                    ['description' => $param['description']]
                );
                if ($param['required']) {
                    $required[] = $param['name'];
                }
            }
        }

        // Add request body
        if ($endpoint['requestBody']) {
            if (isset($endpoint['requestBody']['schema']['properties'])) {
                foreach ($endpoint['requestBody']['schema']['properties'] as $name => $schema) {
                    $properties[$name] = $schema;
                }
            } else {
                // Single body parameter
                $properties['body'] = $endpoint['requestBody']['schema'];
                if ($endpoint['requestBody']['required']) {
                    $required[] = 'body';
                }
            }
        }

        $schema = [
            'type' => 'object',
            'properties' => $properties,
        ];

        if (! empty($required)) {
            $schema['required'] = $required;
        }

        return $this->arrayToPhp($schema, 2);
    }

    /**
     * Generate execute method content
     */
    protected function generateExecuteMethod(array $endpoint, string $baseUrlEnv): string
    {
        $method = strtolower($endpoint['method']);
        $path = $endpoint['path'];

        // Build validation rules
        $validationRules = $this->generateValidationRules($endpoint);

        // Build HTTP request
        $httpRequest = $this->generateHttpRequest($endpoint, $baseUrlEnv);

        return <<<PHP
        // Validate input
        \$validator = Validator::make(\$input, {$validationRules});
        
        if (\$validator->fails()) {
            return [
                'error' => 'Validation failed',
                'errors' => \$validator->errors()->toArray(),
            ];
        }

{$httpRequest}
PHP;
    }

    /**
     * Generate validation rules
     */
    protected function generateValidationRules(array $endpoint): string
    {
        $rules = [];

        foreach ($endpoint['parameters'] as $param) {
            $rule = [];

            if ($param['required']) {
                $rule[] = 'required';
            } else {
                $rule[] = 'sometimes';
            }

            // Add type validation
            switch ($param['schema']['type'] ?? 'string') {
                case 'integer':
                    $rule[] = 'integer';
                    break;
                case 'number':
                    $rule[] = 'numeric';
                    break;
                case 'boolean':
                    $rule[] = 'boolean';
                    break;
                case 'array':
                    $rule[] = 'array';
                    break;
                case 'object':
                    $rule[] = 'array';
                    break;
                default:
                    $rule[] = 'string';
            }

            // Add enum validation
            if (isset($param['schema']['enum'])) {
                $rule[] = 'in:'.implode(',', $param['schema']['enum']);
            }

            $rules[$param['name']] = implode('|', $rule);
        }

        // Add request body validation
        if ($endpoint['requestBody'] && $endpoint['requestBody']['required']) {
            if (isset($endpoint['requestBody']['schema']['properties'])) {
                foreach ($endpoint['requestBody']['schema']['properties'] as $name => $schema) {
                    $rule = ['required'];

                    switch ($schema['type'] ?? 'string') {
                        case 'integer':
                            $rule[] = 'integer';
                            break;
                        case 'number':
                            $rule[] = 'numeric';
                            break;
                        case 'boolean':
                            $rule[] = 'boolean';
                            break;
                        case 'array':
                            $rule[] = 'array';
                            break;
                        case 'object':
                            $rule[] = 'array';
                            break;
                        default:
                            $rule[] = 'string';
                    }

                    $rules[$name] = implode('|', $rule);
                }
            } else {
                $rules['body'] = 'required';
            }
        }

        return $this->arrayToPhp($rules, 2);
    }

    /**
     * Generate HTTP request code
     */
    protected function generateHttpRequest(array $endpoint, string $baseUrlEnv): string
    {
        $method = strtolower($endpoint['method']);
        $path = $endpoint['path'];

        // Replace path parameters
        $pathParams = [];
        foreach ($endpoint['parameters'] as $param) {
            if ($param['in'] === 'path') {
                $pathParams[] = "'{{{$param['name']}}}' => \$input['{$param['name']}'] ?? ''";
            }
        }

        // Build query parameters
        $queryParams = [];
        foreach ($endpoint['parameters'] as $param) {
            if ($param['in'] === 'query') {
                $queryParams[] = "'{$param['name']}' => \$input['{$param['name']}'] ?? null";
            }
        }

        // Build request body
        $hasBody = $endpoint['requestBody'] !== null;

        $code = "        // Build URL\n";
        $code .= "        \$baseUrl = env('{$baseUrlEnv}', '');\n";

        if (! empty($pathParams)) {
            $replacements = [];
            foreach ($endpoint['parameters'] as $param) {
                if ($param['in'] === 'path') {
                    $replacements[] = "'{{{$param['name']}}}', \$input['{$param['name']}'] ?? ''";
                }
            }
            $code .= "        \$path = str_replace(\n";
            $code .= '            ['.implode(', ', array_map(fn ($r) => explode(', ', $r)[0], $replacements))."],\n";
            $code .= '            ['.implode(', ', array_map(fn ($r) => explode(', ', $r)[1], $replacements))."],\n";
            $code .= "            '{$path}'\n";
            $code .= "        );\n";
        } else {
            $code .= "        \$path = '{$path}';\n";
        }

        $code .= "        \$url = rtrim(\$baseUrl, '/') . \$path;\n\n";

        // Add authentication if needed
        if (! empty($endpoint['security'])) {
            $code .= $this->generateAuthenticationCode($endpoint);
        }

        $code .= "        // Make HTTP request\n";
        $code .= "        try {\n";
        $code .= '            $response = Http::';

        // Add authentication headers
        if (! empty($endpoint['security'])) {
            $code .= 'withHeaders($headers)->';
        }

        // Add timeout
        $code .= 'timeout(30)->';

        // Add method and parameters
        if ($method === 'get' || $method === 'delete' || $method === 'head') {
            $code .= "{$method}(\$url";
            if (! empty($queryParams)) {
                $code .= ', ['.implode(', ', $queryParams).']';
            }
            $code .= ");\n";
        } else {
            $code .= "{$method}(\$url";
            if ($hasBody) {
                if (isset($endpoint['requestBody']['schema']['properties'])) {
                    // Multiple body parameters
                    $bodyData = [];
                    foreach ($endpoint['requestBody']['schema']['properties'] as $name => $schema) {
                        $bodyData[] = "'{$name}' => \$input['{$name}'] ?? null";
                    }
                    $code .= ', ['.implode(', ', $bodyData).']';
                } else {
                    // Single body parameter
                    $code .= ", \$input['body'] ?? []";
                }
            }
            if (! empty($queryParams)) {
                $code .= ', ['.implode(', ', $queryParams).']';
            }
            $code .= ");\n";
        }

        $code .= "\n";
        $code .= "            if (\$response->successful()) {\n";
        $code .= "                return \$response->json() ?: \$response->body();\n";
        $code .= "            }\n\n";
        $code .= "            return [\n";
        $code .= "                'error' => 'Request failed',\n";
        $code .= "                'status' => \$response->status(),\n";
        $code .= "                'message' => \$response->body(),\n";
        $code .= "            ];\n";
        $code .= "        } catch (\\Exception \$e) {\n";
        $code .= "            return [\n";
        $code .= "                'error' => 'Request exception',\n";
        $code .= "                'message' => \$e->getMessage(),\n";
        $code .= "            ];\n";
        $code .= '        }';

        return $code;
    }

    /**
     * Generate authentication code
     */
    protected function generateAuthenticationCode(array $endpoint): string
    {
        $code = "        // Setup authentication\n";
        $code .= "        \$headers = [];\n";

        foreach ($endpoint['security'] as $security) {
            foreach ($security as $name => $scopes) {
                if (isset($endpoint['securityDefinitions'][$name])) {
                    $def = $endpoint['securityDefinitions'][$name];

                    if ($def['type'] === 'apiKey') {
                        $envVar = 'SWAGGER_API_KEY_'.strtoupper(str_replace('-', '_', $name));

                        if ($def['in'] === 'header') {
                            $code .= "        \$headers['{$def['name']}'] = env('{$envVar}', '');\n";
                        }
                        // Query parameter API keys would be added to query params
                    } elseif ($def['type'] === 'oauth2' || $def['type'] === 'http') {
                        $envVar = 'SWAGGER_BEARER_TOKEN';
                        $code .= "        \$headers['Authorization'] = 'Bearer ' . env('{$envVar}', '');\n";
                    }
                }
            }
        }

        $code .= "\n";

        return $code;
    }

    /**
     * Convert array to PHP code
     */
    protected function arrayToPhp(array $array, int $indent = 0): string
    {
        if (empty($array)) {
            return '[]';
        }

        $spaces = str_repeat('    ', $indent);
        $innerSpaces = str_repeat('    ', $indent + 1);

        $isAssoc = array_keys($array) !== range(0, count($array) - 1);

        $code = "[\n";

        foreach ($array as $key => $value) {
            $code .= $innerSpaces;

            if ($isAssoc) {
                $code .= "'".$this->escapeString((string) $key)."' => ";
            }

            if (is_array($value)) {
                $code .= $this->arrayToPhp($value, $indent + 1);
            } elseif (is_bool($value)) {
                $code .= $value ? 'true' : 'false';
            } elseif (is_null($value)) {
                $code .= 'null';
            } elseif (is_numeric($value)) {
                $code .= $value;
            } else {
                $code .= "'".$this->escapeString((string) $value)."'";
            }

            $code .= ",\n";
        }

        $code .= $spaces.']';

        return $code;
    }
}
