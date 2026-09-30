<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * RefreshDatabase drops every table before the tests run. Refuse to do that
     * unless the connection points at a *_test database (see phpunit.xml).
     */
    protected function beforeRefreshingDatabase()
    {
        $database = (string) config('database.connections.' . config('database.default') . '.database');

        if (! str_ends_with($database, '_test')) {
            throw new \RuntimeException(
                "Refusing to refresh database [{$database}]: tests must use a database ending in _test."
            );
        }
    }
}
