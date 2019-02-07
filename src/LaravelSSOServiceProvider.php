<?php

namespace Brexis\LaravelSSO;

use Illuminate\Support\ServiceProvider;

class LaravelSSOServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/laravel-sso.php' => config_path('laravel-sso.php'),
        ]);
        
        $this->loadRoutesFrom(__DIR__.'/routes.php');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/laravel-sso.php', 'laravel-sso'
        );
    }
}
