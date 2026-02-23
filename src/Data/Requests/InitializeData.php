<?php

namespace OPGG\LaravelMcpServer\Data\Requests;

use OPGG\LaravelMcpServer\Protocol\MCPProtocol;

/**
 * Initial connection request data.
 */
class InitializeData
{
    public string $protocolVersion;

    public array $capabilities;

    /**
     * @var array<string, mixed>
     */
    public array $clientInfo;

    /**
     * @param  array<string, mixed>  $capabilities
     * @param  array<string, mixed>  $clientInfo
     */
    public function __construct(string $protocolVersion, array $capabilities, array $clientInfo = [])
    {
        $this->protocolVersion = $protocolVersion;
        $this->capabilities = $capabilities;
        $this->clientInfo = $clientInfo;
    }

    public static function fromArray(array $data): self
    {
        $protocolVersion = $data['protocolVersion'] ?? $data['version'] ?? MCPProtocol::PROTOCOL_VERSION;
        if (! is_string($protocolVersion) || $protocolVersion === '') {
            $protocolVersion = MCPProtocol::PROTOCOL_VERSION;
        }

        return new self(
            $protocolVersion,
            is_array($data['capabilities'] ?? null) ? $data['capabilities'] : [],
            is_array($data['clientInfo'] ?? null) ? $data['clientInfo'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'protocolVersion' => $this->protocolVersion,
            'capabilities' => $this->capabilities,
            'clientInfo' => $this->clientInfo,
        ];
    }
}
