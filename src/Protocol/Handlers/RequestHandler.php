<?php

namespace OPGG\LaravelMcpServer\Protocol\Handlers;

interface RequestHandler
{
    public function execute(string $method, ?array $params = null): array;

    public function isHandle(string $method): bool;
}
