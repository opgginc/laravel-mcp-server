<?php

namespace OPGG\LaravelMcpServer\Data\Resources\JsonRpc;

use OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException;

/**
 * Represents a JSON-RPC error response according to the specification.
 * This class transforms a JsonRpcErrorException into the standard JSON-RPC error object format.
 *
 * @see https://www.jsonrpc.org/specification#error_object
 */
class JsonRpcErrorResource
{
    /**
     * The exception containing the JSON-RPC error details.
     */
    protected JsonRpcErrorException $exception;

    /**
     * The request ID. Should be the same as the ID of the Request object that caused the error.
     * Null for notifications or if the ID could not be determined.
     */
    protected string|int|null $id;

    /**
     * Constructor for JsonRpcErrorResource.
     *
     * @param  \OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException  $exception  The exception representing the JSON-RPC error.
     * @param  string|int|null  $id  The ID of the original request, if available.
     */
    public function __construct(JsonRpcErrorException $exception, string|int|null $id = null)
    {
        $this->exception = $exception;
        $this->id = $id;
    }

    /**
     * Converts the error resource into a JSON-RPC compliant array format.
     *
     * @return array{jsonrpc: string, error: array{code: int, message: string, data?: mixed}, id?: string|int|null} The JSON-RPC error response array.
     */
    public function toResponse(): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $this->id,
            'error' => $this->exception->toArray(),
        ];
    }
}
