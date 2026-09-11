<?php

namespace Breadthe\StarterTimezone;

use Breadthe\StarterTimezone\Commands\InstallCommand;
use Illuminate\Support\ServiceProvider;

class StarterTimezoneServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }
}
