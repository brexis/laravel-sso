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
    protected $routeMiddleware = [

    ];

    /**
     * The middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'sso-api' => [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class
        ]
    ];

    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/laravel-sso.php' => config_path('laravel-sso.php'),
        ]);

        // Add sso guard
        $this->extendAuthGuard();

        // Register route middlewares
        $this->registerRouteMiddlewares();

        // Register middleware groups
        $this->registerMiddlewareGroups();

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

    /**
     * Extend Laravel's Auth.
     *
     * @return void
     */
    protected function extendAuthGuard()
    {
        $this->app['auth']->extend('sso', function ($app, $name, array $config) {
            $guard = new SSOGuard(
                $app['auth']->createUserProvider($config['provider']),
                new ClientBrokerManager,
                $app['request']
            );

            $app->refresh('request', $guard, 'setRequest');

            return $guard;
        });
    }

    /**
     * Register route middlewares.
     *
     * @return void
     */
    protected function registerRouteMiddlewares()
    {
        $router = $this->app['router'];

        $method = method_exists($router, 'aliasMiddleware') ? 'aliasMiddleware' : 'middleware';

        foreach ($this->routeMiddleware as $alias => $middleware) {
            $router->$method($alias, $middleware);
        }
    }

    /**
     * Register middleware groups.
     *
     * @return void
     */
    protected function registerMiddlewareGroups()
    {
        $router = $this->app['router'];

        foreach ($this->middlewareGroups as $group => $middlewares) {
            $router->middlewareGroup($group, $middlewares);
        }
    }
}
