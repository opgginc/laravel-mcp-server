<?php

namespace OPGG\LaravelMcpServer\Transports\SeeAdapters;

/**
 * Interface SseAdapterInterface
 *
 * Defines the contract for SSE message queue adapters in the MCP server.
 * These adapters handle message queuing for Server-Sent Events connections.
 * Implementations include Redis, NATS, and InMemory adapters.
 *
 * @see https://modelcontextprotocol.io/docs/concepts/transports
 */
interface SseAdapterInterface
{
    /**
     * Add a message to the queue for a specific client
     *
     * @param  string  $clientId  The unique identifier for the client
     * @param  string  $message  The message to be queued
     *
     * @throws \Exception If the message cannot be added to the queue
     */
    public function pushMessage(string $clientId, string $message): void;

    /**
     * Remove all messages for a specific client
     *
     * @param  string  $clientId  The unique identifier for the client
     *
     * @throws \Exception If the messages cannot be removed
     */
    public function removeAllMessages(string $clientId): void;

    /**
     * Receive all messages for a specific client without removing them
     *
     * @param  string  $clientId  The unique identifier for the client
     * @return array<string> Array of messages
     *
     * @throws \Exception If the messages cannot be retrieved
     */
    public function receiveMessages(string $clientId): array;

    /**
     * Pop the oldest message from the queue for a specific client
     *
     * @param  string  $clientId  The unique identifier for the client
     * @return string|null The message or null if the queue is empty
     *
     * @throws \Exception If the message cannot be popped
     */
    public function popMessage(string $clientId): ?string;

    /**
     * Check if there are any messages in the queue for a specific client
     *
     * @param  string  $clientId  The unique identifier for the client
     * @return bool True if there are messages, false otherwise
     */
    public function hasMessages(string $clientId): bool;

    /**
     * Get the number of messages in the queue for a specific client
     *
     * @param  string  $clientId  The unique identifier for the client
     * @return int The number of messages
     */
    public function getMessageCount(string $clientId): int;

    /**
     * Initialize the adapter with any required configuration
     *
     * @param  array  $config  Configuration options for the adapter
     *
     * @throws \Exception If initialization fails
     */
    public function initialize(array $config): void;
}
