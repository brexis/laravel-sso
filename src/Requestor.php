<?php

namespace Brexis\LaravelSSO;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;

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
     * Generate new checksum
     *
     * @param string $type
     * @param string $token
     * @param string $secret
     * @return string
     */
    public function request($sid, $method, $url, $params = [])
    {
        try {
            $response = $this->client->request($method, $url, [
                'query' => $method === 'GET' ? $params : [],
                'form_params' => $method === 'POST' ? $params : [],
                'headers' => [
                    'Authorization' => 'Bearer ' . $sid,
                    'Accept' => 'application/json'
                ]
            ]);
        } catch (RequestException $e) {
            dump(Psr7\str($e->getRequest()));
            if ($e->hasResponse()) {
                dd(Psr7\str($e->getResponse()));
            }
        }
    }
}
