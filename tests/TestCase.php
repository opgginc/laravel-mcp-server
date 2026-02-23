<?php

namespace OPGG\LaravelMcpServer\Tests;

use OPGG\LaravelMcpServer\LaravelMcpServerServiceProvider;
use OPGG\LaravelMcpServer\Server\McpServerFactory;
use OPGG\LaravelMcpServer\Services\ToolService\ToolRepository;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        ToolRepository::clearSchemaCache();
        if ($this->app->bound(McpServerFactory::class)) {
            $this->app->make(McpServerFactory::class)->clearCache();
        }
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelMcpServerServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
         foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__ . '/database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
         }
         */
    }
}
