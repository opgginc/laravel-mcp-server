<?php

namespace OPGG\LaravelMcpServer\Protocol\Handlers;

interface NotificationHandler
{
    public function execute(?array $params = null): array;

    public function isHandle(string $method): bool;
}
