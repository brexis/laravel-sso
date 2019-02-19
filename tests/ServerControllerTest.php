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
        $response = $this->get('/sso/server/attach', []);

        $response->assertStatus(400);
        $response->assertSee('The broker field is required');
        $response->assertSee('The token field is required');
        $response->assertSee('The checksum field is required');
    }

    public function testShouldThrownExceptionIfReturnUrlIsNotDefined()
    {
        Models\App::create(['app_id' => 'appid', 'secret' => 'SeCrEt']);
        $query = http_build_query([
            'broker' => 'appid', 'token' => 'token', 'checksum' => 'checksum'
        ]);
        $response = $this->get('/sso/server/attach?' . $query);

        $response->assertStatus(400);
        $response->assertSee('No return url specified');
    }

    public function testShouldThrownExceptionIfChecksumIsInvalid()
    {
        Models\App::create(['app_id' => 'appid', 'secret' => 'SeCrEt']);
        $query = http_build_query([
            'broker' => 'appid', 'token' => 'token',
            'return_url' => 'http://localhost', 'checksum' => 'checksum',
        ]);
        $response = $this->get('/sso/server/attach?' . $query);

        $response->assertStatus(400);
        $response->assertSee('Invalid checksum');
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
        $response = $this->get('/sso/server/attach?' .$query);

        $response->assertRedirect('http://localhost');
        $this->assertEquals($this->session->get($sid), '{}');

        // With callback
        $query = http_build_query([
            'broker' => 'appid', 'token' => $token,
            'checksum' => $checksum, 'callback' => 'apply_callback'
        ]);
        $response = $this->get('/sso/server/attach?'. $query);

        $response->assertOk();
        $response->assertSee('apply_callback({"success":"attached"}, 200)');

        // With json
        $query = http_build_query([
            'broker' => 'appid', 'token' => $token, 'checksum' => $checksum
        ]);
        $response = $this->json('get', '/sso/server/attach?'. $query);

        $response->assertOk();
        $response->assertJson(['success' => 'attached']);
    }

    public function testShouldFailAuthenticateWithoutAttached()
    {
        $this->withoutExceptionHandling();
        $this->expectException(NotAttachedException::class);
        $this->expectExceptionMessage('Client broker not attached.');

        $secret = 'SeCrEt';
        Models\App::create(['app_id' => 'appid', 'secret' => $secret]);
        $token = $this->generateToken();
        $sid   = $this->generateSessionId('appid', $token, $secret);
        $checksum = hash('sha256', 'attach' . $token . $secret);

        // With redirect
        $response = $this->post('/sso/server/login', [
            'access_token' => $sid,
            'email' => 'admin@admin.com', 'password' => 'secret'
        ]);
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

        $response->assertStatus(401);
        $response->assertJson(['success' => false]);
    }

    public function testShouldAuthenticateWithEmail()
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

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'user' => $user->toArray()
        ]);
    }

    public function testShouldAuthenticateWithUsername()
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
            'username' => 'admin', 'password' => 'secret', 'login' => 'username'
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'user' => $user->toArray()
        ]);
    }

    public function testShouldFailReturnUserProfile()
    {
        $this->withoutExceptionHandling();
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Unauthorized');

        $secret = 'SeCrEt';
        Models\App::create(['app_id' => 'appid', 'secret' => $secret]);
        $user = Models\User::create([
            'username' => 'admin', 'email' => 'admin@admin.com',
            'password' => bcrypt('secret')
        ]);

        $token = $this->generateToken();
        $sid   = $this->generateSessionId('appid', $token, $secret);
        $checksum = hash('sha256', 'attach' . $token . $secret);

        $response = $this->get('/sso/server/profile?access_token=' .$sid);
    }

    public function testShouldReturnUserProfile()
    {
        $this->withoutExceptionHandling();

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

        $response->assertOk();
        $response->assertJson($user->toArray());
    }
}
