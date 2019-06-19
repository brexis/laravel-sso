<?php

namespace Brexis\LaravelSSO\Test;

use Brexis\LaravelSSO\LaravelSSOServiceProvider;
use Models\User;
use Models\App;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;

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
        $app['config']->set('app.key', 'base64:lSHpE8/8DtS4DmvHsOrfs7cs0clyjAlKhj4+BUYB3u8=');
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

        $app['db']->connection()->getSchemaBuilder()->create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->unsignedInteger('app_id')->nullable();
            $table->timestamps();
            $table->foreign('app_id')->references('id')->on('apps');
        });

        $app['db']->connection()->getSchemaBuilder()->create('authorizations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('app_id');
            $table->unsignedInteger('role_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('app_id')->references('id')->on('apps');
            $table->foreign('role_id')->references('id')->on('roles');
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

    public function createMockClient($status, $body = null, $headers = [])
    {
        $this->container = [];
        $history = Middleware::history($this->container);
        $body = json_encode($body);
        $response = new Response($status, $headers, $body);
        $mock = new MockHandler([$response]);
        $stack = HandlerStack::create($mock);
        // Add the history middleware to the handler stack.
        $stack->push($history);
        $client = new Client(['handler' => $stack]);

        return $client;
    }

    protected function exceptRequest($path, $method, $query = null, $body = null)
    {
        // Iterate over the requests and responses
        foreach ($this->container as $transaction) {
            $request = $transaction['request'];
            $this->assertEquals($request->getUri()->getPath(), $path);
            $this->assertEquals($request->getMethod(), $method);
            if ($query) {
                parse_str($request->getUri()->getQuery(), $request_query);
                $this->assertArraySubset($query, $request_query);
            }
            if ($body) {
                $request_body = json_decode($request->getBody()->getContents(), true);
                $this->assertArraySubset($body, $request_body);
            }
        }
    }
}
