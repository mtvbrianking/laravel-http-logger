<?php

namespace Bmatovu\HttpLogger\Tests;

use Bmatovu\HttpLogger\HttpLoggerServiceProvider;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @param Application $app
     *
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app)
    {
        return [
            HttpLoggerServiceProvider::class,
        ];
    }
}
