<?php

namespace Brexis\LaravelSSO;

use Brexis\LaravelSSO\Exceptions\InvalidClientException;
use Brexis\LaravelSSO\Encription;

/**
 * Class ClientBrokerManager
 */
class ClientBrokerManager
{
    /**
     * @var Brexis\LaravelSSO\Encription
     */
    protected $encription;

    public function __construct($httpClient = null)
    {
        $this->encription = new Encription;
        $this->requestor = new Requestor($httpClient);
    }

    /**
     * Return the client id
     *
     * @return string
     * @throw Brexis\LaravelSSO\Exceptions\InvalidClientException
     */
    public function clientId()
    {
        $client_id = config('laravel-sso.broker_client_id');

        if (empty($client_id)) {
            throw new InvalidClientException(
                'Invalid client id. Please make sure the client id is'.
                ' defined in config.'
            );
        }

        return $client_id;
    }

    /**
     * Return the client secret
     *
     * @return string
     * @throw Brexis\LaravelSSO\Exceptions\InvalidClientException
     */
    public function clientSecret()
    {
        $client_secret = config('laravel-sso.broker_client_secret');

        if (empty($client_secret)) {
            throw new InvalidClientException(
                'Invalid client secret. Please make sure the client secret is'.
                ' defined in config.'
            );
        }

        return $client_secret;
    }

    /**
     * Return the server url
     *
     * @return string
     * @throw Brexis\LaravelSSO\Exceptions\InvalidClientException
     */
    public function serverUrl($path = '')
    {
        $server_url = config('laravel-sso.broker_server_url');

        if (empty($server_url)) {
            throw new InvalidClientException(
                'Invalid server url. Please make sure the server url is'.
                ' defined in config.'
            );
        }

        return $server_url . $path;
    }

    /**
     * Generate an unique session token
     *
     * @return
     */
    public function generateClientToken()
    {
        return $this->encription->randomToken();
    }

    /**
     * Return the session name used to store session id.
     *
     * @return string
     */
    public function sessionName()
    {
        return 'sso_token_' . preg_replace('/[_\W]+/', '_', strtolower($this->clientId()));
    }

    /**
     * Return the session id
     *
     * @param string $token The client generated token
     * @return string
     */
    public function sessionId($token)
    {
        $checksum = $this->encription->generateChecksum(
            'session', $token, $this->clientSecret()
        );

        return "SSO-{$this->clientId()}-{$token}-$checksum";
    }

    /**
     * Generate the attach checksum. Use the encription algorithm.
     *
     * @param string $token
     * @return string
     */
    public function generateAttachChecksum($token)
    {
        return $this->encription->generateChecksum(
            'attach', $token, $this->clientSecret()
        );
    }

    public function login($token, $credentials)
    {
        $url = $this->serverUrl('/login');
        $sid = $this->sessionId($token);

        return $this->requestor->request($sid, 'POST', $url, $credentials);
    }

    public function profile($token)
    {
        $url = $this->serverUrl('/profile');
        $sid = $this->sessionId($token);

        return $this->requestor->request($sid, 'GET', $url);
    }
}
