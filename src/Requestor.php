<?php

namespace Brexis\LaravelSSO;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use Brexis\LaravelSSO\Exceptions\InvalidSessionIdException;
use Brexis\LaravelSSO\Exceptions\InvalidClientException;
use Brexis\LaravelSSO\Exceptions\NotAttachedException;

/**
 * Encription class
 */
class Requestor
{
    protected $client;

    public function __construct($client = null)
    {
        $this->client = $client ?: new Client;
    }

    /**
     * Check if debug mode is enabled
     *
     * @return bool
     */
    public function debugEnabled()
    {
        return config('laravel-sso.debug') === true;
    }

    /**
     * Generate new checksum
     *
     * @param string $type
     * @param string $token
     * @param string $secret
     * @return string
     */
    public function request($sid, $method, $url, $params = [], $headers = [])
    {
        try {
            $headers = array_merge($headers, [
                'Authorization' => 'Bearer ' . $sid,
                'Accept' => 'application/json'
            ]);

            $response = $this->client->request($method, $url, [
                'query' => $method === 'GET' ? $params : [],
                'form_params' => $method === 'POST' ? $params : [],
                'headers' => $headers
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            if ($this->debugEnabled()) {
                if ($e->getRequest()) {
                    \Log::debug(Psr7\str($e->getRequest()));
                }

                if ($e->getResponse()) {
                    \Log::debug(Psr7\str($e->getResponse()));
                }
            }

            $this->throwException($e);

            return false;
        }
    }

    /**
     * Trhow exception base on request exception
     * @throw Brexis\LaravelSSO\Exceptions\InvalidSessionIdException
     * @throw Brexis\LaravelSSO\Exceptions\InvalidClientException
     * @throw Brexis\LaravelSSO\Exceptions\UnauthorizedException
     * @throw Brexis\LaravelSSO\Exceptions\NotAttachedException
     */
    protected function throwException(RequestException $e)
    {
        $request  = $e->getRequest();
        $response = $e->getResponse();
        $status   = $response->getStatusCode();
        $body     = $response->getBody();
        $body->rewind();
        $jsonResponse = json_decode($body->getContents(), true);

        if ($jsonResponse && array_key_exists('code', $jsonResponse)) {
            switch($jsonResponse['code']) {
                case 'invalid_session_id':
                    throw new InvalidSessionIdException($jsonResponse['message'], $status);
                    break;
                case 'invalid_client_id':
                    throw new InvalidClientException($jsonResponse['message']);
                    break;
                case 'not_attached':
                    throw new NotAttachedException($status, $jsonResponse['message']);
                    break;
            }
        }
    }
}
