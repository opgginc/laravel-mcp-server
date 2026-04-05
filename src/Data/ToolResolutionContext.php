<?php

namespace OPGG\LaravelMcpServer\Data;

final class ToolResolutionContext
{
    /**
     * @param  array<int|string, mixed>  $queryParameters
     * @param  array<string, mixed>|null  $requestMessage
     */
    public function __construct(
        public readonly array $queryParameters = [],
        public readonly ?array $requestMessage = null,
    ) {}
}
