<?php

namespace Brexis\LaravelSSO\Test;

use Brexis\LaravelSSO\ServerBrokerManager;
use Brexis\LaravelSSO\Session\ServerSessionManager;
use Brexis\LaravelSSO\Http\Middleware\ServerAuthenticate;
use Brexis\LaravelSSO\Http\Middleware\ValidateBroker;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Auth;

class MiddlewareTest extends TestCase
{
    protected $broker;

    protected $session;

    protected $authenticateMiddleware;

    protected $validateBrokerMiddleware;

    public function setUp()
    {
        parent::setUp();

        $this->broker = new ServerBrokerManager;
        $this->session = new ServerSessionManager;

        $this->authenticateMiddleware = new ServerAuthenticate($this->broker, $this->session);
        $this->validateBrokerMiddleware = new ValidateBroker($this->broker);

        $this->app['config']->set('auth.providers.users.model', Models\User::class);
        $this->app['config']->set('laravel-sso.brokers.model', Models\App::class);
        $this->app['config']->set('laravel-sso.brokers.id_field', 'app_id');
        $this->app['config']->set('laravel-sso.brokers.secret_field', 'secret');
    }

    public function testShouldThrownExceptionIfBrokerIsNotValid()
    {
        Models\App::create(['app_id' => 'appid', 'secret' => 'SeCrEt']);

        $token = $this->generateToken();
        $sid   = $this->generateSessionId('appid', $token, 'secret');
        $request = new Request(['access_token' => $sid]);

        $response = $this->validateBrokerMiddleware->handle($request, function () { });

        $this->assertEquals($response->getContent(), '{"code":"invalid_session_id","message":"Checksum failed: Client IP address may have changed"}');
    }

    public function testShouldNotThrownExceptionIfBrokerIsValid()
    {
        Models\App::create(['app_id' => 'appid', 'secret' => 'SeCrEt']);

        $token = $this->generateToken();
        $sid   = $this->generateSessionId('appid', $token, 'SeCrEt');
        $request = new Request(['access_token' => $sid]);

        $response = $this->validateBrokerMiddleware->handle($request, function () {
            return (new Response())->setContent('<html></html>');
        });

        $this->assertEquals($response->status(), 200);
    }

    public function testShouldThrownExceptionIfBrokerIsNotValidForServerAuthenticate()
    {
        Models\App::create(['app_id' => 'appid', 'secret' => 'SeCrEt']);

        $token = $this->generateToken();
        $sid   = $this->generateSessionId('appid', $token, 'secret');
        $this->session->start($sid);
        $request = new Request(['access_token' => $sid]);

        $response = $this->authenticateMiddleware->handle($request, function () { });
        $this->assertEquals($response->getContent(), '{"code":"invalid_session_id","message":"Checksum failed: Client IP address may have changed"}');
    }

    public function testShouldThrownExceptionIfAuthenticationFailed()
    {
        Models\App::create(['app_id' => 'appid', 'secret' => 'SeCrEt']);

        $token = $this->generateToken();
        $sid   = $this->generateSessionId('appid', $token, 'SeCrEt');
        $this->session->start($sid);
        $request = new Request(['access_token' => $sid]);

        $response = $this->authenticateMiddleware->handle($request, function () { });
        $this->assertEquals($response->getContent(), '{"code":"unauthorized","message":"Unauthorized."}');
    }

    public function testShouldSuccessAuthentication()
    {
        Models\App::create(['app_id' => 'appid', 'secret' => 'SeCrEt']);
        $user = Models\User::create([
            'username' => 'admin', 'email' => 'admin@admin.com',
            'password' => bcrypt('secret')
        ]);

        $token = $this->generateToken();
        $sid   = $this->generateSessionId('appid', $token, 'SeCrEt');
        $this->session->start($sid);
        $this->session->setUserData($sid, json_encode(['username' => 'admin']));

        $request = new Request(['access_token' => $sid, 'username' => 'admin', 'password' => 'secret']);

        $response = $this->authenticateMiddleware->handle($request, function ($request) {
            return (new Response())->setContent('<html></html>');
        });

        $this->assertEquals($response->status(), 200);
        $this->assertEquals(Auth::user()->id, $user->id);
    }
}
