<?php

namespace Brexis\LaravelSSO\Test;

use Brexis\LaravelSSO\ServerBrokerManager;
use Brexis\LaravelSSO\SessionManager;
use Brexis\LaravelSSO\Exceptions\UnauthorizedException;
use Brexis\LaravelSSO\Exceptions\NotAttachedException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ServerControllerTest extends TestCase
{
    protected $broker;

    protected $session;

    protected $authenticateMiddleware;

    protected $validateBrokerMiddleware;

    public function setUp()
    {
        parent::setUp();

        $this->broker = new ServerBrokerManager;
        $this->session = new SessionManager;

        $this->app['config']->set('auth.providers.users.model', Models\User::class);
        $this->app['config']->set('laravel-sso.brokers.model', Models\App::class);
        $this->app['config']->set('laravel-sso.brokers.id_field', 'app_id');
        $this->app['config']->set('laravel-sso.brokers.secret_field', 'secret');
    }

    public function testShouldThrownExceptionIfParamsAreNotDefined()
    {
        $this->get('/sso/server/attach', []);

        $this->assertResponseStatus(400);
        $this->see('The broker field is required');
        $this->see('The token field is required');
        $this->see('The checksum field is required');
    }

    public function testShouldThrownExceptionIfReturnUrlIsNotDefined()
    {
        Models\App::create(['app_id' => 'appid', 'secret' => 'SeCrEt']);
        $query = http_build_query([
            'broker' => 'appid', 'token' => 'token', 'checksum' => 'checksum'
        ]);
        $this->get('/sso/server/attach?' . $query);

        $this->assertResponseStatus(400);
        $this->see('No return url specified');
    }

    public function testShouldThrownExceptionIfChecksumIsInvalid()
    {
        Models\App::create(['app_id' => 'appid', 'secret' => 'SeCrEt']);
        $query = http_build_query([
            'broker' => 'appid', 'token' => 'token',
            'return_url' => 'http://localhost', 'checksum' => 'checksum',
        ]);
        $this->get('/sso/server/attach?' . $query);

        $this->assertResponseStatus(400);
        $this->see('Invalid checksum');
    }

    public function testShouldAttachTheBrocker()
    {
        $secret = 'SeCrEt';
        Models\App::create(['app_id' => 'appid', 'secret' => $secret]);
        $token = $this->generateToken();
        $sid   = $this->generateSessionId('appid', $token, $secret);
        $checksum = hash('sha256', 'attach' . $token . $secret);

        // With redirect
        $query = http_build_query([
            'broker' => 'appid', 'token' => $token,
            'checksum' => $checksum, 'return_url' => 'http://localhost'
        ]);
        $this->get('/sso/server/attach?' .$query);

        $this->assertRedirectedTo('http://localhost');
        $this->assertEquals($this->session->get($sid), '{}');

        // With callback
        $query = http_build_query([
            'broker' => 'appid', 'token' => $token,
            'checksum' => $checksum, 'callback' => 'apply_callback'
        ]);
        $this->get('/sso/server/attach?'. $query);

        $this->assertResponseOk();
        $this->see('apply_callback({"success":"attached"}, 200)');

        // With json
        $query = http_build_query([
            'broker' => 'appid', 'token' => $token, 'checksum' => $checksum
        ]);
        $this->json('get', '/sso/server/attach?'. $query);

        $this->assertResponseOk();
        $this->seeJson(['success' => 'attached']);
    }

    public function testShouldFailAuthenticateWithoutAttached()
    {
        $secret = 'SeCrEt';
        Models\App::create(['app_id' => 'appid', 'secret' => $secret]);
        $token = $this->generateToken();
        $sid   = $this->generateSessionId('appid', $token, $secret);
        $checksum = hash('sha256', 'attach' . $token . $secret);

        // With redirect
        $this->post('/sso/server/login', [
            'access_token' => $sid,
            'email' => 'admin@admin.com', 'password' => 'secret'
        ]);

        $this->assertTrue($this->response->exception instanceof NotAttachedException);
        $this->assertEquals($this->response->exception->getMessage(), 'Client broker not attached.');
    }

    public function testShouldFailAuthenticate()
    {
        $secret = 'SeCrEt';
        Models\App::create(['app_id' => 'appid', 'secret' => $secret]);
        $token = $this->generateToken();
        $sid   = $this->generateSessionId('appid', $token, $secret);
        $checksum = hash('sha256', 'attach' . $token . $secret);

        $query = http_build_query([
            'broker' => 'appid', 'token' => $token, 'checksum' => $checksum,
        ]);
        $response = $this->json('GET', '/sso/server/attach?'. $query);

        $response = $this->post('/sso/server/login', [
            'access_token' => $sid,
            'email' => 'admin@admin.com', 'password' => 'secret'
        ]);

        $this->assertResponseStatus(401);
        $this->seeJson(['success' => false]);
    }

    public function testShouldAuthenticate()
    {
        $secret = 'SeCrEt';
        Models\App::create(['app_id' => 'appid', 'secret' => $secret]);
        $user = Models\User::create([
            'username' => 'admin', 'email' => 'admin@admin.com',
            'password' => bcrypt('secret')
        ]);

        $token = $this->generateToken();
        $sid   = $this->generateSessionId('appid', $token, $secret);
        $checksum = hash('sha256', 'attach' . $token . $secret);

        $query = http_build_query([
            'broker' => 'appid', 'token' => $token, 'checksum' => $checksum
        ]);
        $this->json('GET', '/sso/server/attach?'. $query);

        $response = $this->post('/sso/server/login', [
            'access_token' => $sid,
            'email' => 'admin@admin.com', 'password' => 'secret'
        ]);

        $this->assertResponseOk();
        $this->seeJson([
            'success' => true,
            'user' => $user->toArray()
        ]);
    }

    public function testShouldFailReturnUserProfile()
    {
        $secret = 'SeCrEt';
        Models\App::create(['app_id' => 'appid', 'secret' => $secret]);
        $user = Models\User::create([
            'username' => 'admin', 'email' => 'admin@admin.com',
            'password' => bcrypt('secret')
        ]);

        $token = $this->generateToken();
        $sid   = $this->generateSessionId('appid', $token, $secret);
        $checksum = hash('sha256', 'attach' . $token . $secret);

        $this->get('/sso/server/profile?access_token=' .$sid);

        $this->assertTrue($this->response->exception instanceof UnauthorizedException);
        $this->assertEquals($this->response->exception->getMessage(), 'Unauthorized');
    }

    public function testShouldReturnUserProfile()
    {
        $secret = 'SeCrEt';
        Models\App::create(['app_id' => 'appid', 'secret' => $secret]);
        $user = Models\User::create([
            'username' => 'admin', 'email' => 'admin@admin.com',
            'password' => bcrypt('secret')
        ]);

        $token = $this->generateToken();
        $sid   = $this->generateSessionId('appid', $token, $secret);
        $checksum = hash('sha256', 'attach' . $token . $secret);

        $query = http_build_query([
            'broker' => 'appid', 'token' => $token, 'checksum' => $checksum
        ]);
        $this->json('GET', '/sso/server/attach?'. $query);

        $response = $this->post('/sso/server/login', [
            'access_token' => $sid,
            'email' => 'admin@admin.com', 'password' => 'secret'
        ]);

        $response = $this->get('/sso/server/profile?access_token=' .$sid);

        $this->assertResponseOk();
        $this->seeJson($user->toArray());
    }
}