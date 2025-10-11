<?php

namespace OPGG\LaravelMcpServer\Tests\Lumen;

use Illuminate\Config\Repository as ConfigRepository;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use MockeryPHPUnitIntegration;

    protected TestingApplication $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new TestingApplication($this->basePath());
        $this->app->instance('path.config', $this->basePath('config'));
        $this->app->instance('config', new ConfigRepository());
        $this->app->alias('config', \Illuminate\Contracts\Config\Repository::class);

        $this->app->withFacades();
        $this->app->withEloquent();
    }

    protected function basePath(string $path = ''): string
    {
        $basePath = realpath(__DIR__.'/../..');

        return $path === '' ? $basePath : $basePath.DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
    }
}
