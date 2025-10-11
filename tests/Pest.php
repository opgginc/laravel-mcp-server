<?php

use OPGG\LaravelMcpServer\Tests\Lumen\TestCase as LumenTestCase;
use OPGG\LaravelMcpServer\Tests\TestCase;

uses(TestCase::class)->in(
    __DIR__.'/Console',
    __DIR__.'/Http',
    __DIR__.'/Services',
    __DIR__.'/Unit',
    __DIR__.'/Utils',
    __DIR__.'/LaravelMcpServerServiceProviderTest.php',
);

uses(LumenTestCase::class)->in(__DIR__.'/Lumen');
