<?php

namespace Brexis\LaravelSSO\Test;

use Brexis\LaravelSSO\ClientBrokerManager;
use Brexis\LaravelSSO\SessionManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Mockery;

class ClientControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Client config
        $this->app['config']->set('laravel-sso.broker_client_id', 'appid');
        $this->app['config']->set('laravel-sso.broker_client_secret', 'SeCrEt');
        $this->app['config']->set('laravel-sso.broker_server_url', 'http://localhost/sso/server');

        // Server config
        $this->app['config']->set('auth.providers.users.model', Models\User::class);
        $this->app['config']->set('laravel-sso.brokers.model', Models\App::class);
        $this->app['config']->set('laravel-sso.brokers.id_field', 'app_id');
        $this->app['config']->set('laravel-sso.brokers.secret_field', 'secret');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testShouldAttachClientToServer()
    {
        $token = 'emnxnx465ugcgsgk4gw0c888w';

        $encr = Mockery::mock('overload:Brexis\LaravelSSO\Encription')->makePartial();
        $encr->shouldReceive('randomToken')
             ->andReturn($token)
             ->shouldReceive('generateChecksum')
             ->andReturn('8611d544c942d45d9478e37329f3c88d9433463e5c5456a01d726bce3b60780f');

        $broker = new ClientBrokerManager;
        $session = new SessionManager;

        $key = $broker->sessionName();
        $checksum = $broker->generateAttachChecksum($token);

        $this->get('/sso/client/attach?return_url=http://localhost');
        $redirect_url = '/sso/server/attach?' . http_build_query([
            'broker' => $broker->clientId(),
            'token' => $token,
            'checksum' => $checksum,
            'return_url'=> 'http://localhost'
        ]);

        $this->assertRedirectedTo('http://localhost' . $redirect_url);
        $this->assertEquals(Session::get($key), $token);

        // Testing Server attach
        Models\App::create(['app_id' => 'appid', 'secret' => 'SeCrEt']);
        $sid = $this->generateSessionId('appid', $token, 'SeCrEt');

        $this->get($redirect_url);

        $this->assertRedirectedTo('http://localhost');
    }
}
