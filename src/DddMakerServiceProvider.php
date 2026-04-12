<?php

declare(strict_types=1);

namespace ImranAhmedOptilius\DddMaker;

use Illuminate\Support\ServiceProvider;
use ImranAhmedOptilius\DddMaker\Commands\MakeFeatureCommand;

class DddMakerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeFeatureCommand::class,
            ]);
        }
    }
}
