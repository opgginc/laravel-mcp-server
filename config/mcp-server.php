<?php

use OPGG\LaravelMcpServer\Services\ToolService\Examples\HelloWorldTool;
use OPGG\LaravelMcpServer\Services\ToolService\Examples\VersionCheckTool;

return [
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
        'name' => 'OP.GG MCP Server',
        'version' => '0.1.0',
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP Server
    | Specify the MCP path.
    |
    | GET  /{default_path}/sse
    | POST /{default_path}/message (This endpoint requires `sessionId` from `/sse`)
    |
    |--------------------------------------------------------------------------
    */
    'default_path' => 'mcp',

    /*
    |--------------------------------------------------------------------------
    | SSE Route Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware to apply to the SSE route (/{default_path}/sse). Use this to protect
    | your SSE endpoint with authentication or other middleware as needed.
    | This will only be applied to the SSE endpoint, not to the message endpoint.
    |
    */
    'middlewares' => [
        // 'auth:api'
    ],

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
    | Adapters function as a pub/sub message broker between clients and the server.
    | When a client sends a message to the server endpoint, the server processes it
    | and publishes a response through the adapter. SSE connections subscribe to
    | these messages and deliver them to the client in real-time.
    |
    | The Redis adapter uses Redis lists as message queues, with each client having
    | its own queue identified by a unique client ID. This enables efficient and
    | scalable real-time communication in distributed environments.
    |
    */
    'sse_adapter' => 'redis',
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
