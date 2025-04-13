<?php

use OPGG\LaravelMcpServer\Services\ToolService\Examples\HelloWorldTool;
use OPGG\LaravelMcpServer\Services\ToolService\Examples\VersionCheckTool;

return [
    /*
    |--------------------------------------------------------------------------
    | MCP Server
    | mcp path를 지정합니다.
    | https://modelcontextprotocol.io/specification/2024-11-05
    |--------------------------------------------------------------------------
    */
    'default_path' => 'mcp',

    /*
    |--------------------------------------------------------------------------
    | Server-Sent Events Provider
    |--------------------------------------------------------------------------
    |
    | When set to true, the MCPServer will be registered as a singleton in the
    | application container. This allows for easy access to the server instance
    | throughout the application when using the SSE transport.
    |
    */
    'server_provider' => 'sse',

    /*
    |--------------------------------------------------------------------------
    | SSE Adapters Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the different SSE adapters available in the MCP server.
    | Each adapter has its own configuration options.
    |
    */
    'see_adapter' => 'redis',
    'adapters' => [
        'redis' => [
            'prefix' => 'mcp_sse_',
            'connection' => env('MCP_REDIS_CONNECTION', 'default'), // database.php redis
            'ttl' => 100,
        ],
        // Add more adapter configurations as needed
    ],

    /*
    |--------------------------------------------------------------------------
    | Server Information
    |--------------------------------------------------------------------------
    |
    | Configuration for the MCPServer instance. These values are used when
    | registering the MCPServer as a singleton in the service container.
    |
    */
    'server' => [
        'name' => 'Laravel MCP Server',
        'version' => '1.0.0',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tools List
    | https://modelcontextprotocol.io/docs/concepts/tools
    |--------------------------------------------------------------------------
    |
    | List of tools supported by the MCP server. These values are used when
    | generating the tool list for the client.
    |
    */
    'tools' => [
        HelloWorldTool::class,
        VersionCheckTool::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Prompts List
    | https://modelcontextprotocol.io/docs/concepts/prompts
    |--------------------------------------------------------------------------
    */
    'prompts' => [
    ],

    /*
    |--------------------------------------------------------------------------
    | Resources List
    | https://modelcontextprotocol.io/docs/concepts/resources
    |--------------------------------------------------------------------------
    */
    'resources' => [
    ],
];
