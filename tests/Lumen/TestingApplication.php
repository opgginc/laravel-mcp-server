<?php

namespace OPGG\LaravelMcpServer\Tests\Lumen;

use Laravel\Lumen\Application;

class TestingApplication extends Application
{
    protected function registerErrorHandling()
    {
        // Disable Lumen's global error handlers during tests to avoid
        // polluting PHPUnit's error handling expectations.
    }
}
