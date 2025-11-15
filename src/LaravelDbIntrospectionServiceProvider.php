<?php

namespace Zuqongtech\LaravelDbIntrospection;


use Illuminate\Support\ServiceProvider;
use Zuqongtech\LaravelDbIntrospection\Console\GenerateModelsFromDatabase;

class LaravelDbIntrospectionServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            // Register artisan commands
            $this->commands([
                GenerateModelsFromDatabase::class,
            ]);

            // Publish config
            $this->publishes([
                __DIR__ . '/../config/zt-introspection.php' => config_path('zt-introspection.php'),
            ], 'config');
        }
    }

    public function register()
    {
        // Merge default config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/zt-introspection.php',
            'zt-introspection'
        );
    }
}
