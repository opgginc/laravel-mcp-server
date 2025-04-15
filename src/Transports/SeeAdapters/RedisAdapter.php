<?php

namespace OPGG\LaravelMcpServer\Transports\SeeAdapters;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Redis Adapter for SSE Transport
 *
 * Implements the SSE Adapter interface using Redis as the backend storage.
 * This adapter uses Redis lists to store messages for each client.
 *
 * @see https://modelcontextprotocol.io/docs/concepts/transports
 */
final class RedisAdapter implements SseAdapterInterface
{
    /**
     * Redis connection instance
     */
    protected mixed $redis;

    /**
     * Redis key prefix for SSE messages
     */
    protected string $keyPrefix = 'mcp_sse_';

    /**
     * Message expiration time in seconds
     */
    protected int $messageTtl = 100;

    /**
     * Initialize the adapter with any required configuration
     *
     * @param  array  $config  Configuration options for the adapter
     *
     * @throws Exception If initialization fails
     */
    public function initialize(array $config): void
    {
        try {
            $connection = $config['connection'] ?? 'default';
            $this->redis = Redis::connection($connection);

            if (isset($config['prefix'])) {
                $this->keyPrefix = $config['prefix'];
            }

            if (isset($config['ttl'])) {
                $this->messageTtl = (int) $config['ttl'];
            }
        } catch (Exception $e) {
            Log::error('Failed to initialize Redis SSE Adapter: '.$e->getMessage());
            throw new Exception('Failed to initialize Redis SSE Adapter: '.$e->getMessage());
        }
    }

    /**
     * Add a message to the queue for a specific client
     *
     * @param  string  $clientId  The unique identifier for the client
     * @param  string  $message  The message to be queued
     *
     * @throws Exception If the message cannot be added to the queue
     */
    public function pushMessage(string $clientId, string $message): void
    {
        try {
            $key = $this->getQueueKey($clientId);

            $this->redis->rpush($key, $message);

            $this->redis->expire($key, $this->messageTtl);

        } catch (Exception $e) {
            Log::error('Failed to add message to Redis queue: '.$e->getMessage());
            throw new Exception('Failed to add message to Redis queue: '.$e->getMessage());
        }
    }

    /**
     * Get the Redis key for a client's message queue
     *
     * @param  string  $clientId  The client ID
     * @return string The Redis key
     */
    protected function getQueueKey(string $clientId): string
    {
        $appName = config('app.name');

        return "{$appName}_{$this->keyPrefix}:client:{$clientId}";
    }

    /**
     * Remove all messages for a specific client
     *
     * @param  string  $clientId  The unique identifier for the client
     *
     * @throws Exception If the messages cannot be removed
     */
    public function removeAllMessages(string $clientId): void
    {
        try {
            $key = $this->getQueueKey($clientId);

            $this->redis->del($key);

        } catch (Exception $e) {
            Log::error('Failed to remove messages from Redis queue: '.$e->getMessage());
            throw new Exception('Failed to remove messages from Redis queue: '.$e->getMessage());
        }
    }

    /**
     * Receive and remove all messages for a specific client
     *
     * @param  string  $clientId  The unique identifier for the client
     * @return array<string> Array of messages
     *
     * @throws Exception If the messages cannot be retrieved
     */
    public function receiveMessages(string $clientId): array
    {
        try {
            $key = $this->getQueueKey($clientId);
            $messages = [];

            while (($message = $this->redis->lpop($key)) !== null && $message !== false) {
                $messages[] = $message;
            }

            return $messages;
        } catch (Exception $e) {
            throw new Exception('Failed to receive messages from Redis queue: '.$e->getMessage());
        }
    }

    /**
     * Pop the oldest message from the queue for a specific client
     *
     * @param  string  $clientId  The unique identifier for the client
     * @return string|null The message or null if the queue is empty
     *
     * @throws Exception If the message cannot be popped
     */
    public function popMessage(string $clientId): ?string
    {
        try {
            $key = $this->getQueueKey($clientId);

            $message = $this->redis->lpop($key);

            if ($message === null || $message === false) {
                return null;
            }

            return $message;
        } catch (Exception $e) {
            Log::error('Failed to pop message from Redis queue: '.$e->getMessage());
            throw new Exception('Failed to pop message from Redis queue: '.$e->getMessage());
        }
    }

    /**
     * Check if there are any messages in the queue for a specific client
     *
     * @param  string  $clientId  The unique identifier for the client
     * @return bool True if there are messages, false otherwise
     */
    public function hasMessages(string $clientId): bool
    {
        try {
            $key = $this->getQueueKey($clientId);

            $count = $this->redis->llen($key);

            return $count > 0;
        } catch (Exception $e) {
            Log::error('Failed to check for messages in Redis queue: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Get the number of messages in the queue for a specific client
     *
     * @param  string  $clientId  The unique identifier for the client
     * @return int The number of messages
     */
    public function getMessageCount(string $clientId): int
    {
        try {
            $key = $this->getQueueKey($clientId);

            $count = $this->redis->llen($key);

            return (int) $count;
        } catch (Exception $e) {
            Log::error('Failed to get message count from Redis queue: '.$e->getMessage());

            return 0;
        }
    }
}
