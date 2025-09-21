<?php

namespace bitoliveira\Approval;

use Illuminate\Support\ServiceProvider;

class ApprovalServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge package config
        $this->mergeConfigFrom(__DIR__ . '/../config/approval.php', 'approval');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish the config file
        $this->publishes([
            __DIR__ . '/../config/approval.php' => config_path('approval.php'),
        ], 'approval-config');

        // Load package migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load API routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Publish migrations so the app can customize timestamps/paths
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'approval-migrations');
    }
}
