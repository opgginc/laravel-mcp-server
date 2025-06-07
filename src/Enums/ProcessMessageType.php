<?php

namespace OPGG\LaravelMcpServer\Enums;

enum ProcessMessageType: string
{
    case SSE = 'SSE';
    case HTTP = 'HTTP';
}
