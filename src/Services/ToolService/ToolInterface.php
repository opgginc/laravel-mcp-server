<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

interface ToolInterface
{
    public function getName(): string;

    public function getDescription(): string;

    public function getInputSchema(): array;

    public function getAnnotations(): array;

    public function execute(array $arguments): mixed;
}
