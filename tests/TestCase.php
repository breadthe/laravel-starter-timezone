<?php

namespace Breadthe\StarterTimezone\Tests;

use Breadthe\StarterTimezone\StarterTimezoneServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            StarterTimezoneServiceProvider::class,
        ];
    }
}
