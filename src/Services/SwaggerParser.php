<?php

namespace OPGG\LaravelMcpServer\Services;

class SwaggerParser
{
    /**
     * Parse Swagger/OpenAPI specification
     */
    public function parse(array $spec): array
    {
        // Detect specification version
        $version = $this->detectVersion($spec);

        if ($version === '2.0') {
            return $this->parseSwagger2($spec);
        } elseif (str_starts_with($version, '3.')) {
            return $this->parseOpenApi3($spec);
        }

        throw new \InvalidArgumentException("Unsupported specification version: {$version}");
    }

    /**
     * Detect specification version
     */
    protected function detectVersion(array $spec): string
    {
        if (isset($spec['swagger'])) {
            return $spec['swagger'];
        }

        if (isset($spec['openapi'])) {
            return $spec['openapi'];
        }

        throw new \InvalidArgumentException('Unable to detect Swagger/OpenAPI version');
    }

    /**
     * Parse Swagger 2.0 specification
     */
    protected function parseSwagger2(array $spec): array
    {
        $endpoints = [];
        $basePath = $spec['basePath'] ?? '';
        $host = $spec['host'] ?? '';
        $schemes = $spec['schemes'] ?? ['https'];
        $baseUrl = "{$schemes[0]}://{$host}{$basePath}";

        $globalSecurity = $spec['security'] ?? [];
        $securityDefinitions = $spec['securityDefinitions'] ?? [];

        foreach ($spec['paths'] ?? [] as $path => $pathItem) {
            foreach ($pathItem as $method => $operation) {
                if (in_array($method, ['get', 'post', 'put', 'patch', 'delete', 'head', 'options'])) {
                    $endpoints[] = $this->parseEndpoint(
                        $path,
                        $method,
                        $operation,
                        $baseUrl,
                        $globalSecurity,
                        $securityDefinitions,
                        '2.0'
                    );
                }
            }
        }

        return $endpoints;
    }

    /**
     * Parse OpenAPI 3.0 specification
     */
    protected function parseOpenApi3(array $spec): array
    {
        $endpoints = [];
        $servers = $spec['servers'] ?? [['url' => '']];
        $baseUrl = $servers[0]['url'] ?? '';

        $globalSecurity = $spec['security'] ?? [];
        $securitySchemes = $spec['components']['securitySchemes'] ?? [];

        foreach ($spec['paths'] ?? [] as $path => $pathItem) {
            foreach ($pathItem as $method => $operation) {
                if (in_array($method, ['get', 'post', 'put', 'patch', 'delete', 'head', 'options'])) {
                    $endpoints[] = $this->parseEndpoint(
                        $path,
                        $method,
                        $operation,
                        $baseUrl,
                        $globalSecurity,
                        $securitySchemes,
                        '3.0'
                    );
                }
            }
        }

        return $endpoints;
    }

    /**
     * Parse individual endpoint
     */
    protected function parseEndpoint(
        string $path,
        string $method,
        array $operation,
        string $baseUrl,
        array $globalSecurity,
        array $securityDefinitions,
        string $version
    ): array {
        $endpoint = [
            'path' => $path,
            'method' => strtoupper($method),
            'operationId' => $operation['operationId'] ?? $this->generateOperationId($method, $path),
            'summary' => $operation['summary'] ?? '',
            'description' => $operation['description'] ?? '',
            'baseUrl' => $baseUrl,
            'parameters' => [],
            'requestBody' => null,
            'security' => $operation['security'] ?? $globalSecurity,
            'securityDefinitions' => $securityDefinitions,
        ];

        // Parse parameters
        if ($version === '2.0') {
            $endpoint['parameters'] = $this->parseSwagger2Parameters($operation['parameters'] ?? []);

            // Handle body parameters separately in Swagger 2.0
            foreach ($operation['parameters'] ?? [] as $param) {
                if (($param['in'] ?? '') === 'body') {
                    $endpoint['requestBody'] = $this->convertSwagger2BodyToSchema($param);
                }
            }
        } else {
            $endpoint['parameters'] = $this->parseOpenApi3Parameters($operation['parameters'] ?? []);

            // Handle requestBody in OpenAPI 3.0
            if (isset($operation['requestBody'])) {
                $endpoint['requestBody'] = $this->parseOpenApi3RequestBody($operation['requestBody']);
            }
        }

        return $endpoint;
    }

    /**
     * Generate operation ID from method and path
     */
    protected function generateOperationId(string $method, string $path): string
    {
        // Convert path to camelCase
        $path = str_replace(['{', '}'], '', $path);
        $parts = explode('/', trim($path, '/'));
        $parts = array_map(function ($part) {
            return ucfirst(str_replace('-', '', ucwords($part, '-')));
        }, $parts);

        return $method.implode('', $parts);
    }

    /**
     * Parse Swagger 2.0 parameters
     */
    protected function parseSwagger2Parameters(array $parameters): array
    {
        $parsed = [];

        foreach ($parameters as $param) {
            if (($param['in'] ?? '') === 'body') {
                continue; // Body parameters handled separately
            }

            $parsed[] = [
                'name' => $param['name'] ?? '',
                'in' => $param['in'] ?? 'query',
                'required' => $param['required'] ?? false,
                'description' => $param['description'] ?? '',
                'type' => $param['type'] ?? 'string',
                'schema' => $this->convertSwagger2TypeToJsonSchema($param),
            ];
        }

        return $parsed;
    }

    /**
     * Parse OpenAPI 3.0 parameters
     */
    protected function parseOpenApi3Parameters(array $parameters): array
    {
        $parsed = [];

        foreach ($parameters as $param) {
            $parsed[] = [
                'name' => $param['name'] ?? '',
                'in' => $param['in'] ?? 'query',
                'required' => $param['required'] ?? false,
                'description' => $param['description'] ?? '',
                'schema' => $param['schema'] ?? ['type' => 'string'],
            ];
        }

        return $parsed;
    }

    /**
     * Convert Swagger 2.0 type to JSON Schema
     */
    protected function convertSwagger2TypeToJsonSchema(array $param): array
    {
        $schema = [
            'type' => $param['type'] ?? 'string',
        ];

        if (isset($param['format'])) {
            $schema['format'] = $param['format'];
        }

        if (isset($param['enum'])) {
            $schema['enum'] = $param['enum'];
        }

        if (isset($param['minimum'])) {
            $schema['minimum'] = $param['minimum'];
        }

        if (isset($param['maximum'])) {
            $schema['maximum'] = $param['maximum'];
        }

        if ($schema['type'] === 'array' && isset($param['items'])) {
            $schema['items'] = $this->convertSwagger2TypeToJsonSchema($param['items']);
        }

        return $schema;
    }

    /**
     * Convert Swagger 2.0 body parameter to schema
     */
    protected function convertSwagger2BodyToSchema(array $param): array
    {
        return [
            'description' => $param['description'] ?? '',
            'required' => $param['required'] ?? false,
            'schema' => $param['schema'] ?? ['type' => 'object'],
        ];
    }

    /**
     * Parse OpenAPI 3.0 requestBody
     */
    protected function parseOpenApi3RequestBody(array $requestBody): array
    {
        $content = $requestBody['content'] ?? [];
        $jsonContent = $content['application/json'] ?? $content['*/*'] ?? null;

        return [
            'description' => $requestBody['description'] ?? '',
            'required' => $requestBody['required'] ?? false,
            'schema' => $jsonContent['schema'] ?? ['type' => 'object'],
        ];
    }
}
