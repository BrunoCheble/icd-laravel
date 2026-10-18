<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * A test that empties the database (RefreshDatabase and the like) only runs on a test database: one whose name
     * ends in "_test" (icd_test, see phpunit.xml). Any other database (local data, production) stops it first.
     */
    protected function setUpTraits()
    {
        $uses = class_uses_recursive(static::class);
        if (isset($uses[RefreshDatabase::class]) || isset($uses[DatabaseMigrations::class]) || isset($uses[DatabaseTruncation::class])) {
            $connection = config('database.default');
            $database = (string) config("database.connections.{$connection}.database");
            if (! str_ends_with($database, '_test')) {
                throw new RuntimeException("Refusing to empty the database \"{$database}\": tests that reset the database only run on a *_test database (see phpunit.xml).");
            }
        }

        return parent::setUpTraits();
    }
}
