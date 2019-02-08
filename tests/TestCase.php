<?php

namespace Brexis\LaravelSSO\Test;

use Brexis\LaravelSSO\LaravelSSOServiceProvider;
use Models\User;
use Models\App;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

abstract class TestCase extends OrchestraTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->setUpDatabase($this->app);
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelSSOServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('auth.providers.users.model', User::class);
    }

    public function setUpDatabase($app)
    {
        $app['db']->connection()->getSchemaBuilder()->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        $app['db']->connection()->getSchemaBuilder()->create('apps', function (Blueprint $table) {
            $table->increments('id');
            $table->string('app_id')->unique();
            $table->string('secret');
            $table->timestamps();
        });
    }

    protected function generateToken()
    {
        return base_convert(md5(uniqid(rand(), true)), 16, 36);
    }

    protected function generateSessionId($brocer_id, $token, $secret)
    {
        return "SSO-{$brocer_id}-{$token}-" . hash('sha256', 'session' . $token . $secret);
    }

}
