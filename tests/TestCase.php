<?php

declare(strict_types=1);

namespace ImranAhmedOptilius\DddMaker\Tests;

use ImranAhmedOptilius\DddMaker\DddMakerServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            DddMakerServiceProvider::class,
        ];
    }
}
