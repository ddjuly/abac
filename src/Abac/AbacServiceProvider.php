<?php

namespace Abac;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Zizaco\Entrust
 */

use Abac\command\CreateTableCommand;
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
        $this->commands('command.abac.create-table');

        // Register blade directives
        $this->bladeDirectives();
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

        $this->app->singleton('command.abac.create-table', function ($app) {
            return new CreateTableCommand();
        });
    }


    /**
     * Get the services provided.
     *
     * @return array
     */
    public function provides()
    {
        return ['abac'];
    }


    /**
     * Register the blade directives
     *
     * @return void
     */
    private function bladeDirectives()
    {
        if (!class_exists('\Blade')) return;

        // Call to Entrust::hasRole
        \Blade::directive('role', function($expression) {
            return "<?php if (\\Abac::hasRole({$expression})) : ?>";
        });

        \Blade::directive('endrole', function($expression) {
            return "<?php endif; // Abac::hasRole ?>";
        });

        // Call to Entrust::permission
        \Blade::directive('permission', function($expression) {
            return "<?php if (\\Abac::hasPermission({$expression})) : ?>";
        });

        \Blade::directive('endpermission', function($expression) {
            return "<?php endif; // Abac::hasPermission ?>";
        });

        // Call to Entrust::ability
        \Blade::directive('ability', function($expression) {
            return "<?php if (\\Abac::ability({$expression})) : ?>";
        });

        \Blade::directive('endability', function($expression) {
            return "<?php endif; // Abac::ability ?>";
        });
    }

}
