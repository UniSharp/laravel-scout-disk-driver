<?php

namespace UniSharp\ScoutDiskEngine;

use UniSharp\ScoutDiskEngine\ScoutDiskEngine;
use Laravel\Scout\EngineManager;
use Illuminate\Support\ServiceProvider;

class ScoutDiskEngineServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ManageIndexes::class,
            ]);
        }
        $this->app->make(EngineManager::class)->extend('mysql', function () {
            return new ScoutDiskEngine();
        });
    }
    /**
     * Register the application services.
     */
    public function register()
    {
    }
}
