<?php

namespace OPGG\LaravelMcpServer\Services\ResourceService\Examples;

use OPGG\LaravelMcpServer\Services\ResourceService\Resource;

class LogFileResource extends Resource
{
    public string $uri = 'file:///logs/example.log';

    public string $name = 'Example Log File';

    public ?string $mimeType = 'text/plain';

    public function read(): array
    {
        $text = "Example log contents\n";

        return [
            'uri' => $this->uri,
            'mimeType' => $this->mimeType,
            'text' => $text,
        ];
    }
}
