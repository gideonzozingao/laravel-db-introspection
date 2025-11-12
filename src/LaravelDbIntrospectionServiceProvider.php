<?php

namespace GideonZozingao\LaravelDbIntrospection;

use App\Console\Commands\GenerateModelsFromDatabase;
use Illuminate\Support\ServiceProvider;


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
                __DIR__ . '/../config/db-introspection.php' => config_path('db-introspection.php'),
            ], 'config');
        }
    }

    public function register()
    {
        // Merge default config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/db-introspection.php',
            'db-introspection'
        );
    }
}
