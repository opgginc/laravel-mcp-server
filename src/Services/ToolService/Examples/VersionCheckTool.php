<?php

namespace OPGG\LaravelMcpServer\Services\ToolService\Examples;

use Illuminate\Support\Facades\App;
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;
use stdClass;

class VersionCheckTool implements ToolInterface
{
    public function getName(): string
    {
        return 'check-version';
    }

    public function getDescription(): string
    {
        return 'Check the current Laravel version.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => new stdClass,
            'required' => [],
        ];
    }

    public function getAnnotations(): array
    {
        return [];
    }

    public function execute(array $arguments): string
    {
        $now = now()->format('Y-m-d H:i:s');
        $version = App::version();

        return "current Version: {$version} - {$now}";
    }
}
