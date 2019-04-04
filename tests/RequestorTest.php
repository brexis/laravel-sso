<?php

namespace Brexis\LaravelSSO\Test;

use Brexis\LaravelSSO\Requestor;
use Brexis\LaravelSSO\Exceptions\InvalidSessionIdException;
use Brexis\LaravelSSO\Exceptions\InvalidClientException;
use Brexis\LaravelSSO\Exceptions\UnauthorizedException;
use Brexis\LaravelSSO\Exceptions\NotAttachedException;
use Auth;

class RequestorTest extends TestCase
{
    public function testShouldSendRequest()
    {
        $client = $this->createMockClient(200, ['id' => 2]);
        $requestor = new Requestor($client);
        $json = $requestor->request('ssid', 'POST', 'http://localhost');

        $this->assertEquals($json, ['id' => 2]);
    }

    public function testShouldThrowInvalidSessionException()
    {
        $this->expectException(InvalidSessionIdException::class);
        $this->expectExceptionMessage('Invalid session id.');

        $client = $this->createMockClient(401, ['code' => 'invalid_session_id', 'message' => 'Invalid session id.']);
        $requestor = new Requestor($client);
        $json = $requestor->request('ssid', 'POST', 'http://localhost');
    }

    public function testShouldThrowInvalidClientException()
    {
        $this->expectException(InvalidClientException::class);
        $this->expectExceptionMessage('Invalid client id.');

        $client = $this->createMockClient(401, ['code' => 'invalid_client_id', 'message' => 'Invalid client id.']);
        $requestor = new Requestor($client);
        $json = $requestor->request('ssid', 'POST', 'http://localhost');
    }

    public function testShouldThrowUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Unauthorized.');

        $client = $this->createMockClient(401, ['code' => 'unauthorized', 'message' => 'Unauthorized.']);
        $requestor = new Requestor($client);
        $json = $requestor->request('ssid', 'POST', 'http://localhost');
    }

    public function testShouldThrowNotAttachedException()
    {
        $this->expectException(NotAttachedException::class);
        $this->expectExceptionMessage('Client not attached');

        $client = $this->createMockClient(401, ['code' => 'not_attached', 'message' => 'Client not attached']);
        $requestor = new Requestor($client);
        $json = $requestor->request('ssid', 'POST', 'http://localhost');
    }
}
