<?php

namespace bitoliveira\Approval\Tests;

use bitoliveira\Approval\ApprovalServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            ApprovalServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Run package and test migrations
        $this->artisan('migrate', ['--database' => 'testbench'])->run();
    }

    protected function defineDatabaseMigrations(): void
    {
        // Load test-specific migrations (e.g., employees table)
        $this->loadMigrationsFrom(__DIR__ . '/Database/migrations');
    }

    protected function getEnvironmentSetUp($app)
    {
        // Use in-memory sqlite
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Disable sanctum authentication for tests (use plain 'api' middleware)
        $app['config']->set('approval.api.middleware', ['api']);
    }
}
