<?php

use OPGG\LaravelMcpServer\Data\Resources\JsonRpc\JsonRpcErrorResource;
use OPGG\LaravelMcpServer\Exceptions\Enums\JsonRpcErrorCode;
use OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException;

test('json rpc error resource keeps zero id values', function () {
    $resource = new JsonRpcErrorResource(
        new JsonRpcErrorException('Invalid payload', JsonRpcErrorCode::INVALID_REQUEST),
        0
    );

    expect($resource->toResponse())->toBe([
        'jsonrpc' => '2.0',
        'id' => 0,
        'error' => [
            'code' => -32600,
            'message' => 'Invalid payload',
        ],
    ]);
});

test('json rpc error resource includes null id when request id is unavailable', function () {
    $resource = new JsonRpcErrorResource(
        new JsonRpcErrorException('Parse error', JsonRpcErrorCode::PARSE_ERROR)
    );

    expect($resource->toResponse())->toBe([
        'jsonrpc' => '2.0',
        'id' => null,
        'error' => [
            'code' => -32700,
            'message' => 'Parse error',
        ],
    ]);
});
