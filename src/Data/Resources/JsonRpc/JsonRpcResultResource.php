<?php

namespace OPGG\LaravelMcpServer\Data\Resources\JsonRpc;

use stdClass;

/**
 * Represents a successful JSON-RPC 2.0 response structure.
 * This resource formats the result and ID according to the JSON-RPC specification.
 */
class JsonRpcResultResource
{
    /**
     * The result data of the JSON-RPC request.
     */
    protected array|stdClass $result;

    /**
     * The identifier established by the client for the JSON-RPC request.
     * Must be the same as the value of the id member in the Request Object.
     * If there was an error in detecting the id in the Request object (e.g. Parse error/Invalid Request), it MUST be Null.
     * For notifications, this is null as notifications don't have IDs.
     */
    protected string|int|null $id;

    /**
     * JsonRpcResultResource constructor.
     *
     * @param  string|int|null  $id  The identifier established by the client. Null for notifications.
     * @param  array|stdClass  $result  The result data from the method execution.
     */
    public function __construct(string|int|null $id, array|stdClass $result)
    {
        $this->result = $result;
        $this->id = $id;
    }

    /**
     * Formats the data into a JSON-RPC 2.0 compliant response array.
     *
     * @return array{jsonrpc: string, id: string|int|null, result: array|stdClass} The JSON-RPC response array.
     */
    public function toResponse(): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $this->id,
            'result' => $this->result,
        ];
    }
}
