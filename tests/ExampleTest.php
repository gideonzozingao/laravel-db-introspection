<?php

namespace Zuqongtech\LaravelDbIntrospection\Tests;

use Orchestra\Testbench\TestCase;
use Zuqongtech\LaravelDbIntrospection\LaravelDbIntrospectionServiceProvider;

abstract class ExampleTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [LaravelDbIntrospectionServiceProvider::class];
    }

    public function test_example()
    {
        $this->assertTrue(true);
    }
}
