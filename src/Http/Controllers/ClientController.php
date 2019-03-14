<?php

namespace Brexis\LaravelSSO\Http\Controllers;

use Brexis\LaravelSSO\ClientBrokerManager;
use Brexis\LaravelSSO\Session\ClientSessionManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ClientController extends Controller
{
    /**
     * @var Brexis\LaravelSSO\ClientBrokerManager
     */
    protected $broker;

    /**
     * @var Brexis\LaravelSSO\ClientSessionManager
     */
    protected $session;

    /**
     * Constructor
     *
     * @param Brexis\LaravelSSO\ClientBrokerManager $broker
     * @param Brexis\LaravelSSO\SessionManager $session
     */
    public function __construct(ClientBrokerManager $broker, ClientSessionManager $session)
    {
        $this->broker = $broker;
        $this->session = $session;
    }

    /**
     * Attach client to server
     * @param Illuminate\Http\Request $request
     *
     * @return Illuminate\Http\Response
     */
    public function attach(Request $request)
    {
        $params     = $request->except(['broker', 'token', 'checksum']);
        $attach_url = $this->getAttachUrl($params);

        return redirect()->away($attach_url);
    }

    /**
     * Return attack url with params
     * @param array $params
     *
     * @return string
     */
    protected function getAttachUrl($params = [])
    {
        $token = $this->generateNewToken();
        $checksum = $this->broker->generateAttachChecksum($token);

        $query = [
            'broker' => $this->broker->clientId(),
            'token' => $token,
            'checksum' => $checksum
        ] + $params;

        return $this->broker->serverUrl('/attach?' . http_build_query($query));
    }

    /**
     * Generate new client token
     *
     * @return string
     */
    protected function generateNewToken()
    {
        $token = $this->broker->generateClientToken();
        $this->broker->saveClientToken($token);

        return $token;
    }
}
