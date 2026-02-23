<?php

namespace OPGG\LaravelMcpServer\Tests\Fixtures\Handlers;

use OPGG\LaravelMcpServer\Server\Request\ToolsCallHandler;

class CustomToolsCallHandler extends ToolsCallHandler
{
    public function execute(string $method, ?array $params = null): array
    {
        $result = parent::execute(method: $method, params: $params);

        if ($method !== 'tools/call') {
            return $result;
        }

        $meta = $result['_meta'] ?? [];
        if (! is_array($meta)) {
            $meta = [];
        }

        return [
            ...$result,
            '_meta' => [
                ...$meta,
                'handler' => 'custom-tools-call-handler',
            ],
        ];
    }
}
