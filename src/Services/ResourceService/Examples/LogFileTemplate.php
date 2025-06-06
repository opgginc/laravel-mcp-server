<?php

namespace OPGG\LaravelMcpServer\Services\ResourceService\Examples;

use OPGG\LaravelMcpServer\Services\ResourceService\ResourceTemplate;

class LogFileTemplate extends ResourceTemplate
{
    public string $uriTemplate = 'file:///logs/{date}.log';

    public string $name = 'Log file by date';

    public ?string $description = 'Access log file for the given date';

    public ?string $mimeType = 'text/plain';
}
