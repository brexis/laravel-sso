<?php

namespace Brexis\LaravelSSO\Test\Session;

use Brexis\LaravelSSO\Session\ClientSessionManager;
use Illuminate\Support\Facades\Session;
use Brexis\LaravelSSO\Test\TestCase;

class ClientSessionManagerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->session = new ClientSessionManager();
    }

    public function testShouldSetSessionInCache()
    {
        $this->assertNull(Session::get('session_id'));

        $this->app['config']->set('laravel-sso.session_ttl', 60);
        $this->session->set('session_id', 'value');

        $this->assertEquals(Session::get('session_id'), 'value');
    }

    public function testShouldSetSessionInCacheForever()
    {
        Session::shouldReceive('forever')
                    ->once()
                    ->with('session_id', 'value');

        $this->app['config']->set('laravel-sso.session_ttl', null);

        $this->session->set('session_id', 'value');
    }

    public function testShouldForgetSessionInCache()
    {
        $this->session->set('session_id', 'value');
        $this->session->forget('session_id');

        $this->assertNull($this->session->get('session_id'));
    }
}
