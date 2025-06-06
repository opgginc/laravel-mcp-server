<?php

namespace OPGG\LaravelMcpServer\Services\SamplingService;

use Illuminate\Support\Str;
use OPGG\LaravelMcpServer\Data\ProcessMessageData;
use OPGG\LaravelMcpServer\Server\MCPServer;

class SamplingService
{
    public function __construct(private MCPServer $server) {}

    /**
     * Request a language model generation from the connected client.
     */
    public function createMessage(string $clientId, Sampler|array $sampler): ProcessMessageData
    {
        $params = $sampler instanceof Sampler ? $sampler->toArray() : $sampler;

        $message = [
            'jsonrpc' => '2.0',
            'id' => Str::uuid()->toString(),
            'method' => 'sampling/createMessage',
            'params' => $params,
        ];

        return $this->server->requestMessage(clientId: $clientId, message: $message);
    }
}
