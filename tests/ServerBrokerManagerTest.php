<?php

namespace Brexis\LaravelSSO\Test;

use Brexis\LaravelSSO\ServerBrokerManager;
use Brexis\LaravelSSO\Exceptions\InvalidSessionIdException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class ServerBrokerManagerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->broker = new ServerBrokerManager();
    }

    public function testShouldThrownExceptionIfBrokerModelDoesNotExist()
    {
        $this->expectException(InvalidSessionIdException::class);
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
        $this->expectException(InvalidSessionIdException::class);
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
        $this->expectException(InvalidSessionIdException::class);
        $this->expectExceptionMessage('Invalid session id');

        $this->app['config']->set('laravel-sso.brokers.model', Models\App::class);
        $this->app['config']->set('laravel-sso.brokers.id_field', 'app_id');
        $this->app['config']->set('laravel-sso.brokers.secret_field', 'secret');

        $this->broker->validateBrokerSessionId('SSO-FakeSessionID');
    }

    public function testShouldNotValidateChecksum()
    {
        $this->expectException(InvalidSessionIdException::class);
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

    public function testShouldReturnBrokerInfoFromSessionId()
    {
        $this->app['config']->set('laravel-sso.brokers.model', Models\App::class);
        $this->app['config']->set('laravel-sso.brokers.id_field', 'app_id');
        $this->app['config']->set('laravel-sso.brokers.secret_field', 'secret');

        Models\App::create(['app_id' => 'appid', 'secret' => 'SeCrEt']);

        $token = $this->generateToken();
        $sid   = $this->generateSessionId('appid', $token, 'SeCrEt');
        list($broker_id, $ntoken) = $this->broker->getBrokerInfoFromSessionId($sid);

        $this->assertEquals($broker_id, 'appid');
        $this->assertEquals($ntoken, $token);
    }

    public function testShouldReturnBrokerFromRequest()
    {
        $this->app['config']->set('laravel-sso.brokers.model', Models\App::class);
        $this->app['config']->set('laravel-sso.brokers.id_field', 'app_id');
        $this->app['config']->set('laravel-sso.brokers.secret_field', 'secret');

        Models\App::create(['app_id' => 'appid', 'secret' => 'SeCrEt']);

        $token = $this->generateToken();
        $sid   = $this->generateSessionId('appid', $token, 'SeCrEt');

        $request = new Request([], ['sso_session' => $sid], [], [], [], ['REQUEST_METHOD' => 'POST']);

        $broker = $this->broker->getBrokerFromRequest($request);

        $this->assertEquals($broker->app_id, 'appid');
        $this->assertEquals($broker->secret, 'SeCrEt');
    }
}
