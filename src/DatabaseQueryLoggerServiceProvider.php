<?php

namespace Elminson\DbLogger;

use Illuminate\Support\ServiceProvider;

class DatabaseQueryLoggerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot()
    {
        // Publish the config file
        $this->publishes([
            __DIR__.'/config/database-logger.php' => config_path('db-logger.php'),
        ], 'config');
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        // Merge config so users can override defaults
        $this->mergeConfigFrom(
            __DIR__.'/config/database-logger.php', 'db-logger'
        );
    }
} 