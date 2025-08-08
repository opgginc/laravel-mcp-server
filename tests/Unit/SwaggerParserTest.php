<?php

namespace OPGG\LaravelMcpServer\Tests\Unit;

use OPGG\LaravelMcpServer\Services\SwaggerParser\SwaggerParser;
use OPGG\LaravelMcpServer\Services\SwaggerParser\SwaggerToMcpConverter;
use PHPUnit\Framework\TestCase;

class SwaggerParserTest extends TestCase
{
    protected SwaggerParser $parser;

    protected string $specPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new SwaggerParser;
        $this->specPath = __DIR__.'/../fixtures/petstore.json';
    }

    public function test_can_load_spec_from_file()
    {
        $this->parser->load($this->specPath);

        $info = $this->parser->getInfo();

        $this->assertEquals('openapi-3.0.0', $info['version']);
        $this->assertEquals('Petstore API', $info['title']);
        $this->assertEquals('https://petstore.example.com/api/v1', $info['baseUrl']);
        $this->assertEquals(4, $info['totalEndpoints']);
        $this->assertContains('pets', $info['tags']);
    }

    public function test_can_extract_endpoints()
    {
        $this->parser->load($this->specPath);

        $endpoints = $this->parser->getEndpoints();

        $this->assertCount(4, $endpoints);

        // Check first endpoint
        $firstEndpoint = $endpoints[0];
        $this->assertEquals('/pets', $firstEndpoint['path']);
        $this->assertEquals('GET', $firstEndpoint['method']);
        $this->assertEquals('listPets', $firstEndpoint['operationId']);
        $this->assertFalse($firstEndpoint['deprecated']);

        // Check parameters
        $this->assertCount(2, $firstEndpoint['parameters']);
        $this->assertEquals('limit', $firstEndpoint['parameters'][0]['name']);
        $this->assertEquals('query', $firstEndpoint['parameters'][0]['in']);
    }

    public function test_can_group_endpoints_by_tag()
    {
        $this->parser->load($this->specPath);

        $byTag = $this->parser->getEndpointsByTag();

        $this->assertArrayHasKey('pets', $byTag);
        $this->assertCount(4, $byTag['pets']);
    }

    public function test_can_extract_security_schemes()
    {
        $this->parser->load($this->specPath);

        $schemes = $this->parser->getSecuritySchemes();

        $this->assertArrayHasKey('bearerAuth', $schemes);
        $this->assertArrayHasKey('apiKey', $schemes);

        $this->assertEquals('http', $schemes['bearerAuth']['type']);
        $this->assertEquals('bearer', $schemes['bearerAuth']['scheme']);

        $this->assertEquals('apiKey', $schemes['apiKey']['type']);
        $this->assertEquals('header', $schemes['apiKey']['in']);
        $this->assertEquals('X-API-Key', $schemes['apiKey']['name']);
    }

    public function test_can_convert_endpoint_to_tool()
    {
        $this->parser->load($this->specPath);

        $converter = new SwaggerToMcpConverter($this->parser);

        $endpoints = $this->parser->getEndpoints();
        $endpoint = $endpoints[0]; // listPets

        $toolParams = $converter->convertEndpointToTool($endpoint, 'ListPetsTool');

        $this->assertEquals('ListPetsTool', $toolParams['className']);
        $this->assertEquals('list-pets', $toolParams['toolName']);
        $this->assertStringContainsString('List all pets', $toolParams['description']);

        // Check input schema
        $this->assertEquals('object', $toolParams['inputSchema']['type']);
        $this->assertArrayHasKey('limit', $toolParams['inputSchema']['properties']);
        $this->assertArrayHasKey('status', $toolParams['inputSchema']['properties']);

        // Check annotations
        $this->assertTrue($toolParams['annotations']['readOnlyHint']);
        $this->assertFalse($toolParams['annotations']['destructiveHint']);

        // Check imports
        $this->assertContains('Illuminate\\Support\\Facades\\Http', $toolParams['imports']);
    }

    public function test_handles_deprecated_endpoints()
    {
        $this->parser->load($this->specPath);

        $endpoints = $this->parser->getEndpoints();

        // Find the deprecated delete endpoint
        $deleteEndpoint = null;
        foreach ($endpoints as $endpoint) {
            if ($endpoint['operationId'] === 'deletePet') {
                $deleteEndpoint = $endpoint;
                break;
            }
        }

        $this->assertNotNull($deleteEndpoint);
        $this->assertTrue($deleteEndpoint['deprecated']);
    }

    public function test_can_generate_class_names()
    {
        $this->parser->load($this->specPath);

        $converter = new SwaggerToMcpConverter($this->parser);

        $endpoints = $this->parser->getEndpoints();

        foreach ($endpoints as $endpoint) {
            $className = $converter->generateClassName($endpoint);

            // Check that class names end with Tool
            $this->assertStringEndsWith('Tool', $className);

            // Check specific names
            if ($endpoint['operationId'] === 'listPets') {
                $this->assertEquals('ListPetsTool', $className);
            } elseif ($endpoint['operationId'] === 'createPet') {
                $this->assertEquals('CreatePetTool', $className);
            } elseif ($endpoint['operationId'] === 'getPetById') {
                $this->assertEquals('GetPetByIdTool', $className);
            }
        }

        // Test path-based naming (without operationId)
        $testEndpoint = [
            'path' => '/lol/{region}/server-stats',
            'method' => 'GET',
            'operationId' => null,
        ];

        $className = $converter->generateClassName($testEndpoint);
        $this->assertEquals('GetLolRegionServerStatsTool', $className);

        // Test with kebab-case and underscores
        $testEndpoint2 = [
            'path' => '/api/v2/user-profiles/{user_id}/match-history',
            'method' => 'POST',
            'operationId' => null,
        ];

        $className2 = $converter->generateClassName($testEndpoint2);
        $this->assertEquals('PostApiV2UserProfilesUserIdMatchHistoryTool', $className2);
    }

    public function test_can_set_custom_base_url()
    {
        $this->parser->load($this->specPath);

        $originalUrl = $this->parser->getBaseUrl();
        $this->assertEquals('https://petstore.example.com/api/v1', $originalUrl);

        $this->parser->setBaseUrl('https://custom.api.com');

        $newUrl = $this->parser->getBaseUrl();
        $this->assertEquals('https://custom.api.com', $newUrl);
    }
}
