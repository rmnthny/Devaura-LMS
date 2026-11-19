<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $application = parent::createApplication();
        $application['migrator']->path($application->basePath('tests/Support/Migrations'));

        return $application;
    }
}
