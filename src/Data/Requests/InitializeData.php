<?php

namespace OPGG\LaravelMcpServer\Data\Requests;

/**
 * Initial connection request data.
 */
class InitializeData
{
    public string $version;

    public array $capabilities;

    public function __construct(string $version, array $capabilities)
    {
        $this->version = $version;
        $this->capabilities = $capabilities;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['version'] ?? '1.0',
            $data['capabilities'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'capabilities' => $this->capabilities,
        ];
    }
}
