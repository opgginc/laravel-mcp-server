<?php

namespace OPGG\LaravelMcpServer\Services;

use Exception;
use Illuminate\Support\Facades\Config;
use OPGG\LaravelMcpServer\Transports\SseAdapters\RedisAdapter;
use OPGG\LaravelMcpServer\Transports\SseAdapters\SseAdapterInterface;

/**
 * Factory for creating and managing SSE adapters.
 *
 * This factory handles the initialization and configuration of SSE adapters,
 * and provides methods to create and retrieve them.
 */
final class SseAdapterFactory
{
    /**
     * The adapter type to use.
     */
    private string $adapterType;

    /**
     * The adapter instance cache.
     */
    private ?SseAdapterInterface $adapter = null;

    /**
     * Constructor.
     *
     * @param  string  $adapterType  The type of SSE adapter to create (e.g., 'redis').
     * @return void
     */
    public function __construct(string $adapterType)
    {
        $this->adapterType = $adapterType;
    }

    /**
     * Create and initialize the SSE adapter.
     *
     * @return SseAdapterInterface The created and initialized SSE adapter instance.
     *
     * @throws Exception If the adapter type is not supported or initialization fails.
     */
    public function createAdapter(): SseAdapterInterface
    {
        if ($this->adapter === null) {
            $this->initializeAdapter();
        }

        return $this->adapter;
    }

    /**
     * Initialize the adapter based on the configured type.
     *
     * @throws Exception If the adapter type is not supported or initialization fails.
     */
    private function initializeAdapter(): void
    {
        $adapterConfig = Config::get('mcp-server.adapters.' . $this->adapterType, []);

        switch ($this->adapterType) {
            case 'redis':
                $this->adapter = new RedisAdapter;
                break;
            default:
                throw new Exception('Unsupported SSE adapter type: ' . $this->adapterType);
        }

        $this->adapter->initialize($adapterConfig);
    }

    /**
     * Get the created adapter instance.
     *
     * @return SseAdapterInterface|null The created adapter instance, or null if not created yet.
     */
    public function getAdapter(): ?SseAdapterInterface
    {
        return $this->adapter;
    }
}
