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

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            if ($this->debugEnabled()) {
                throw $e;
            }

            return false;
        }
    }
}
