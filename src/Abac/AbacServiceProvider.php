<?php

namespace Abac;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Zizaco\Entrust
 */

use Illuminate\Support\ServiceProvider;

class AbacServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        // Publish config files
//        $this->publishes([
//            __DIR__.'/../config/config.php' => app()->basePath() . '/config/entrust.php',
//        ]);

        // Register commands
//        $this->commands('command.entrust.migration');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('abac', function ($app) {
            return new Abac($app);
        });

        $this->app->alias('abac', 'Abac\Abac');

    }


    /**
     * Get the services provided.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'command.entrust.migration'
        ];
    }
}
