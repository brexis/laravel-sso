<?php

namespace Brexis\LaravelSSO\Test {
    use Brexis\LaravelSSO\ClientBrokerManager;
    use Brexis\LaravelSSO\SessionManager;
    use Illuminate\Http\Request;
    use Illuminate\Http\Response;
    use Illuminate\Support\Facades\Cache;

    class ClientControllerTest extends TestCase
    {
        protected $broker;

        protected $session;

        public function setUp()
        {
            parent::setUp();

            $this->broker = new ClientBrokerManager;
            $this->session = new SessionManager;

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

        public function testShouldAttachClientToServer()
        {
            $this->withoutExceptionHandling();

            $token = 'emnxnx465ugcgsgk4gw0c888w';
            $key = $this->broker->sessionName();
            $checksum = $this->broker->generateAttachChecksum($token);

            $response = $this->get('/sso/client/attach?return_url=http://localhost');
            $redirect_url = '/sso/server/attach?' . http_build_query([
                'broker' => $this->broker->clientId(),
                'token' => $token,
                'checksum' => $checksum,
                'return_url'=> 'http://localhost'
            ]);

            $response->assertRedirect('http://localhost' . $redirect_url);
            $this->assertEquals(Cache::get($key), $token);

            // Testing Server attach
            Models\App::create(['app_id' => 'appid', 'secret' => 'SeCrEt']);
            $sid = $this->generateSessionId('appid', $token, 'SeCrEt');

            $response = $this->get($redirect_url);

            $response->assertRedirect('http://localhost');
            $this->assertEquals($this->session->get($sid), '{}');
        }
    }
}

namespace Brexis\LaravelSSO {
    function base_convert($e)
    {
        return 'emnxnx465ugcgsgk4gw0c888w';
    }
}