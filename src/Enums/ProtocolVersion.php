<?php

namespace OPGG\LaravelMcpServer\Enums;

enum ProtocolVersion: string
{
    case V2025_11_25 = '2025-11-25';
    case V2025_06_18 = '2025-06-18';

    public static function latest(): self
    {
        return self::V2025_11_25;
    }
}
