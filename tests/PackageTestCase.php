<?php

declare(strict_types=1);

namespace Mikehins\Cart\Tests;

use Mikehins\Cart\CartServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class PackageTestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            CartServiceProvider::class,
        ];
    }
}
