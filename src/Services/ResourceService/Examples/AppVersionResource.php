<?php

namespace OPGG\LaravelMcpServer\Services\ResourceService\Examples;

use Illuminate\Support\Facades\App;
use OPGG\LaravelMcpServer\Services\ResourceService\Resource;

class AppVersionResource extends Resource
{
    public function __construct()
    {
        parent::__construct(
            uri: 'example://app/version',
            name: 'Application Version',
            description: 'Current Laravel application version',
            mimeType: 'text/plain',
            reader: fn () => ['text' => App::version()]
        );
    }
}
