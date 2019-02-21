<?php

namespace Brexis\LaravelSSO;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;

class LaravelSSOServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */

     /**
     * The middleware aliases.
     *
     * @var array
     */
    protected $middlewareAliases = [
        
    ];

    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/laravel-sso.php' => config_path('laravel-sso.php'),
        ]);

        $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Add sso guard
        $this->extendAuthGuard();

        // Register middlewares alias
        $this->extendAuthGuard();
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

    /**
     * Extend Laravel's Auth.
     *
     * @return void
     */
    protected function extendAuthGuard()
    {
        $this->app['auth']->extend('sso', function ($app, $name, array $config) {
            return new SSOGuard(
                $app['auth']->createUserProvider($config['provider']),
                new ClientBrokerManager
            );
        });
    }

    /**
     * Register middlewares alias.
     *
     * @return void
     */
    protected function registerMiddlewares()
    {
        $router = $this->app['router'];

        $method = method_exists($router, 'aliasMiddleware') ? 'aliasMiddleware' : 'middleware';

        foreach ($this->middlewareAliases as $alias => $middleware) {
            $router->$method($alias, $middleware);
        }
    }
}
