<?php

namespace Brexis\LaravelSSO\Test;

use Brexis\LaravelSSO\ServerBrokerManager;
use Brexis\LaravelSSO\SessionManager;
use Brexis\LaravelSSO\Exceptions\NotAttachedException;
use Brexis\LaravelSSO\Events;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Event;

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
        $this->assertEquals($this->session->get($sid), Session::getId());

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

        $response->assertJson([
            'code' => 'not_attached',
            'message' => 'Client broker not attached.'
        ]);
    }

    public function testShouldFailAuthenticate()
    {
        Event::fake();

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

        Event::assertDispatched(Events\LoginFailed::class, function ($e) {
            $this->assertEquals($e->credentials, ['email' => 'admin@admin.com', 'password' => 'secret']);
            return true;
        });
    }

    public function testShouldAuthenticateWithEmail()
    {
        Event::fake();

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

        Event::assertDispatched(Events\Authenticated::class, function ($e) use ($user) {
            return $e->user->id === $user->id;
        });

        Event::assertDispatched(Events\LoginSucceeded::class, function ($e) use ($user) {
            return $e->user->id === $user->id;
        });

        $response->assertOk();
        $response->assertJson($user->toArray());
    }

    public function testShouldAuthenticateWithUsername()
    {
        Event::fake();

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

        Event::assertDispatched(Events\Authenticated::class, function ($e) use ($user) {
            return $e->user->id === $user->id;
        });

        Event::assertDispatched(Events\LoginSucceeded::class, function ($e) use ($user) {
            return $e->user->id === $user->id;
        });


        $response->assertOk();
        $response->assertJson($user->toArray());
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

        $response = $this->get('/sso/server/profile?access_token=' .$sid);

        $response->assertJson([
            'code' => 'unauthorized',
            'message' => 'Unauthorized.'
        ]);
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

        $this->app['config']->set('laravel-sso.user_info', function($user, $broker) {
            $this->assertEquals($broker->app_id, 'appid');
            return ['id' => $user->id];
        });

        $response = $this->get('/sso/server/profile?access_token=' .$sid);

        $response->assertOk();
        $response->assertJson(['id' => $user->id]);
    }

    public function testShouldLogoutUser()
    {
        Event::fake();

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

        $this->post('/sso/server/logout', [
            'access_token' => $sid
        ]);

        Event::assertDispatched(Events\Logout::class, function ($e) use ($user) {
            return $e->user->id === $user->id;
        });

        $response = $this->get('/sso/server/profile?access_token=' .$sid);

        $response->assertJson([
            'code' => 'unauthorized',
            'message' => 'Unauthorized.'
        ]);
    }
}
