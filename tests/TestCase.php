<?php

namespace Tests;

use Bausch\FlarumLaravelSession\Actions\HandleIdentifiedUser;
use Bausch\FlarumLaravelSession\FlarumLaravelSession;
use Bausch\FlarumLaravelSession\ServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Tests\Models\User;

abstract class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        $this->artisan('migrate', ['--database' => 'testing']);
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Flarum configuration
        $app['config']->set('flarum', [
            'url' => 'https://flarum.tld',
            'session' => [
                'cookie' => 'flarum_session',
                'path' => 'storage',
            ],
            'db_connection' => 'flarum',
        ]);

        // Local database
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Flarum database
        $app['config']->set('database.connections.flarum', [
            'driver' => 'sqlite',
            'url' => null,
            'database' => __DIR__.'/database/flarum.sqlite',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        FlarumLaravelSession::handleIdentifiedUser(HandleIdentifiedUser::class);
        FlarumLaravelSession::useUserModel(User::class);
    }

    /**
     * Load Service Provider.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }
}
