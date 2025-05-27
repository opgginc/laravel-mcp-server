<?php

namespace OPGG\LaravelMcpServer\Protocol\Handlers;

use stdClass;

interface RequestHandler
{
    public function execute(string $method, ?array $params = null): array|stdClass;

    public function isHandle(string $method): bool;
}
