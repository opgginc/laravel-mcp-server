<?php

namespace OPGG\LaravelMcpServer\Services\SamplingService;

use OPGG\LaravelMcpServer\Data\ProcessMessageData;

interface SamplingServerInterface
{
    /**
     * Send a request message to a specific client.
     *
     * @param  string  $clientId  The identifier of the target client.
     * @param  array<string, mixed>  $message  The request message payload.
     */
    public function requestMessage(string $clientId, array $message): ProcessMessageData;
}
