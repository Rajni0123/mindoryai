<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        if (! file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
            $this->markTestSkipped('Composer dependencies not installed.');
        }

        parent::setUp();
    }

    public function createApplication()
    {
        $app = require dirname(__DIR__) . '/bootstrap/app.php';

        return $app->create();
    }
}
