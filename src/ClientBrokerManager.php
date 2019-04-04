<?php

namespace Brexis\LaravelSSO;

use Brexis\LaravelSSO\Exceptions\InvalidClientException;
use Brexis\LaravelSSO\Exceptions\NotAttachedException;
use Brexis\LaravelSSO\Session\ClientSessionManager;
use Illuminate\Http\Request;

/**
 * Class ClientBrokerManager
 */
class ClientBrokerManager
{
    /**
     * @var Brexis\LaravelSSO\Encription
     */
    protected $encription;

    /**
     * @var Brexis\LaravelSSO\Session\ClientSessionManager
     */
    protected $session;

    /**
     * @var Brexis\LaravelSSO\Requestor
     */
    protected $requestor;

    /**
     * Constructor
     *
     * @param Brexis\LaravelSSO\Requestor $httpClient
     */
    public function __construct($httpClient = null)
    {
        $this->encription = new Encription;
        $this->session = new ClientSessionManager;
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
     * @return string
     */
    public function generateClientToken()
    {
        return $this->encription->randomToken();
    }

    /**
     * Save session token
     */
    public function saveClientToken($token)
    {
        $key = $this->sessionName();

        $this->session->set($key, $token);
    }

    /**
     * Return session token
     *
     * @return string
     */
    public function getClientToken()
    {
        $key = $this->sessionName();

        return $this->session->get($key);
    }

    /**
     * Clear session token
     */
    public function clearClientToken()
    {
        $key = $this->sessionName();

        $this->session->forget($key);
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
     * Check if session is attached
     *
     * @return bool
     */
    public function isAttached()
    {
        return !is_null($this->getClientToken());
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

    /**
     * Send login request
     *
     * @param array $credentials
     * @param Illuminate\Http\Request $request
     *
     * @return bool|array
     */
    public function login($credentials, Request $request = null)
    {
        $url   = $this->serverUrl('/login');
        $token = $this->getClientToken();
        $sid   = $this->sessionId($token);
        $headers = $this->agentHeaders($request);

        return $this->sendRequestWithExceptionCatch($sid, 'POST', $url, $credentials, $headers);
    }

    /**
     * Send profile request
     * @param Illuminate\Http\Request $request
     *
     * @return bool|array
     */
    public function profile(Request $request = null)
    {
        $url   = $this->serverUrl('/profile');
        $token = $this->getClientToken();
        $sid   = $this->sessionId($token);
        $headers = $this->agentHeaders($request);

        return $this->sendRequestWithExceptionCatch($sid, 'GET', $url, [], $headers);
    }

    /**
     * Send logout request
     * @param Illuminate\Http\Request $request
     *
     * @return bool
     */
    public function logout(Request $request = null)
    {
        $url   = $this->serverUrl('/logout');
        $token = $this->getClientToken();
        $sid   = $this->sessionId($token);
        $headers = $this->agentHeaders($request);

        $response = $this->sendRequestWithExceptionCatch($sid, 'POST', $url, [], $headers);
        return $response['success'] === true;
    }

    protected function sendRequestWithExceptionCatch($sid, $method, $url, $params = [], $headers = [])
    {
        try {
            return $this->requestor->request($sid, $method, $url, $params, $headers);
        } catch(NotAttachedException $e) {
            $this->clearClientToken();
            throw $e;
        }
    }

    /**
     * Add agent headers
     *
     * @param Illuminate\Http\Request $request
     */
    protected function agentHeaders(Request $request = null)
    {
        $headers = [];

        if ($request) {
            $headers = [
                'SSO-User-Agent' => $request->header('User-Agent'),
                'SSO-REMOTE-ADDR' => $request->ip()
            ];
        }

        return $headers;
    }
}
