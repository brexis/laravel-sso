<?php

namespace Brexis\LaravelSSO\Test;

use Brexis\LaravelSSO\BrokerManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Http\Request;

class BrokerManagerTest extends TestCase
{
    protected function generateToken()
    {
        return base_convert(md5(uniqid(rand(), true)), 16, 36);
    }

    protected function generateSessionId($brocer_id, $token, $secret)
    {
        return "SSO-{$brocer_id}-{$token}-" . hash('sha256', 'session' . $token . $secret);
    }

    public function setUp()
    {
        parent::setUp();
        $this->broker = new BrokerManager();
    }

    public function testShouldThrownExceptionIfBrokerModelDoesNotExist()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Class Models\TestApp does not exist');

        $this->app['config']->set('laravel-sso.brokers.model', 'Models\TestApp');

        $this->broker->brokerModel();
    }

    public function testShouldNotThrownExceptionIfBrokerModelExists()
    {
        $this->app['config']->set('laravel-sso.brokers.model', Models\App::class);

        $class = $this->broker->brokerModel();
        $this->assertEquals($class, Models\App::class);
    }

    public function testShouldThrownExceptionIfModelDoesNotExist()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Class Models\TestApp does not exist');

        $this->app['config']->set('laravel-sso.brokers.model', 'Models\TestApp');

        $this->broker->findBrokerById(1);
    }

    public function testShouldNotThrownExceptionIfModelExists()
    {
        $this->app['config']->set('laravel-sso.brokers.model', Models\App::class);
        $this->app['config']->set('laravel-sso.brokers.id_field', 'app_id');
        $this->app['config']->set('laravel-sso.brokers.secret_field', 'secret');

        Models\App::create(['app_id' => 'app-id', 'secret' => 'SeCrEt']);

        $model = $this->broker->findBrokerById('app-id');
        $this->assertEquals($model->app_id, 'app-id');

        $secret = $this->broker->findBrokerSecret($model);
        $this->assertEquals($secret, 'SeCrEt');
    }

    public function testShouldNotValidateSessionId()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid session id');

        $this->app['config']->set('laravel-sso.brokers.model', Models\App::class);
        $this->app['config']->set('laravel-sso.brokers.id_field', 'app_id');
        $this->app['config']->set('laravel-sso.brokers.secret_field', 'secret');

        $this->broker->validateBrokerSessionId('SSO-FakeSessionID');
    }

    public function testShouldNotValidateChecksum()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Checksum failed: Client IP address may have changed');

        $this->app['config']->set('laravel-sso.brokers.model', Models\App::class);
        $this->app['config']->set('laravel-sso.brokers.id_field', 'app_id');
        $this->app['config']->set('laravel-sso.brokers.secret_field', 'secret');

        Models\App::create(['app_id' => 'appid', 'secret' => 'SeCrEt']);

        $token = $this->generateToken();
        $sid   = $this->generateSessionId('appid', $token, 'secret');

        $this->broker->validateBrokerSessionId($sid);
    }

    public function testShouldValidateBrokerSessionId()
    {
        $this->app['config']->set('laravel-sso.brokers.model', Models\App::class);
        $this->app['config']->set('laravel-sso.brokers.id_field', 'app_id');
        $this->app['config']->set('laravel-sso.brokers.secret_field', 'secret');

        Models\App::create(['app_id' => 'appid', 'secret' => 'SeCrEt']);

        $token = $this->generateToken();
        $sid   = $this->generateSessionId('appid', $token, 'SeCrEt');

        $this->assertEquals($this->broker->validateBrokerSessionId($sid), 'appid');
    }

    public function testShouldReturnSessionIdFromRequest()
    {
        $request = new Request([], [], [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer SeCrEt']);
        $this->assertEquals($this->broker->getBrokerSessionId($request), 'SeCrEt');

        $request = new Request(['access_token' => 'AccessToken']);
        $this->assertEquals($this->broker->getBrokerSessionId($request), 'AccessToken');

        $request = new Request([], ['sso_session' => 'SsoToken'], [], [], [], ['REQUEST_METHOD' => 'POST']);
        $this->assertEquals($this->broker->getBrokerSessionId($request), 'SsoToken');
    }
}
