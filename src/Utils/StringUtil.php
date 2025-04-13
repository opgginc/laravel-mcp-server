<?php

namespace OPGG\LaravelMcpServer\Utils;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class StringUtil
{
    public static function makeEndpoint(string $sessionId): string
    {
        $path = Config::get('mcp-server.default_path');
        $path = Str::start($path, '/');

        return "{$path}/message?sessionId={$sessionId}";
    }
}
