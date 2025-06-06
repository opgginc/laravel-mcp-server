<?php

namespace OPGG\LaravelMcpServer\Enums;

enum ProcessMessageType: string
{
    case PROTOCOL = 'PROTOCOL'; // To be deleted upon SSE support termination
    case SSE = 'SSE';
    case HTTP = 'HTTP';
}
