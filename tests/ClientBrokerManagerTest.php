<?php

namespace Brexis\LaravelSSO\Test;

use Brexis\LaravelSSO\ClientBrokerManager;
use Brexis\LaravelSSO\Exceptions\InvalidClientException;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Middleware;

class ClientBrokerManagerTest extends TestCase
{
    protected $container = [];

    public function setUp()
    {
        parent::setUp();
        $this->broker = new ClientBrokerManager;
    }

    protected function mockHttpClient($status, $body = null, $headers = [])
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

    protected function exceptHttpRequest($path, $method, $headers = null, $query = null, $body = null)
    {
        // Iterate over the requests and responses
        foreach ($this->container as $transaction) {
            $request = $transaction['request'];
            $this->assertEquals($request->getUri()->getPath(), $path);
            $this->assertEquals($request->getMethod(), $method);

            if ($headers) {
                $request_headers = $request->getHeaders();
                $this->assertArraySubset($headers, $request_headers);
            }

            if ($query) {
                parse_str($request->getUri()->getQuery(), $request_query);
                $this->assertArraySubset($query, $request_query);
            }

            if ($body) {
                parse_str($request->getBody()->getContents(), $request_body);
                $this->assertArraySubset($body, $request_body);
            }
        }
    }

    public function testShouldThrownExceptionIfBrokerClientIdNotExist()
    {
        $this->expectException(InvalidClientException::class);

        $this->broker->clientId();
    }

    public function testShouldThrownExceptionIfBrokerClientSecretNotExist()
    {
        $this->expectException(InvalidClientException::class);

        $this->broker->clientSecret();
    }

    public function testShouldThrownExceptionIfBrokerServerUrlNotExist()
    {
        $this->expectException(InvalidClientException::class);

        $this->broker->serverUrl();
    }

    public function testShouldValidateClientConfiguration()
    {
        $this->app['config']->set('laravel-sso.broker_client_id', 'app_id');
        $this->app['config']->set('laravel-sso.broker_client_secret', 'app_secret');
        $this->app['config']->set('laravel-sso.broker_server_url', 'http://localhost');

        $this->broker->clientId();
        $this->broker->clientSecret();
        $this->broker->serverUrl();

        $this->assertTrue(true);
    }

    public function testShouldReturnSessionId()
    {
        $this->app['config']->set('laravel-sso.broker_client_id', 'app_id');
        $this->app['config']->set('laravel-sso.broker_client_secret', 'app_secret');

        $this->assertEquals(
            $this->broker->sessionId('token'),
            'SSO-app_id-token-d40a37c49cfaeaf0daa065b9c7e9762548433b4ae34b6bbf5c889045f1b9f9e0'
        );
    }

    public function testShouldSendLoginRequest()
    {
        $this->app['config']->set('laravel-sso.broker_client_id', 'app_id');
        $this->app['config']->set('laravel-sso.broker_client_secret', 'app_secret');
        $this->app['config']->set('laravel-sso.broker_server_url', 'http://localhost/sso/server');

        $client = $this->mockHttpClient(200);

        $broker = new ClientBrokerManager($client);
        $token = $broker->generateClientToken();
        $broker->saveClientToken($token);
        $sid = $broker->sessionId($token);

        $broker->login(['username' => 'admin', 'password' => 'secret']);

        $this->exceptHttpRequest('/sso/server/login', 'POST', [
            'Authorization' => ['Bearer ' . $sid],
            'Accept' => ['application/json']
        ], null, ['username' => 'admin', 'password' => 'secret']);
    }

    public function testShouldSendProfileRequest()
    {
        $this->app['config']->set('laravel-sso.broker_client_id', 'app_id');
        $this->app['config']->set('laravel-sso.broker_client_secret', 'app_secret');
        $this->app['config']->set('laravel-sso.broker_server_url', 'http://localhost/sso/server');

        $client = $this->mockHttpClient(200);

        $broker = new ClientBrokerManager($client);
        $token = $broker->generateClientToken();
        $broker->saveClientToken($token);
        $sid = $broker->sessionId($token);

        $broker->profile();

        $this->exceptHttpRequest('/sso/server/profile', 'GET', [
            'Authorization' => ['Bearer ' . $sid],
            'Accept' => ['application/json']
        ]);
    }
}
