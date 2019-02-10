<?php

namespace Brexis\LaravelSSO\Http\Controllers;

use Brexis\LaravelSSO\ClientBrokerManager;
use Brexis\LaravelSSO\SessionManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ClientController extends Controller
{
    protected $broker;

    protected $session;

    public function __construct(ClientBrokerManager $broker, SessionManager $session)
    {
        $this->broker = $broker;
        $this->session = $session;
    }

    public function attach(Request $request)
    {
        $params     = $request->except(['broker', 'token', 'checksum']);
        $attach_url = $this->getAttachUrl($params);

        return redirect()->away($attach_url);
    }

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

    protected function generateNewToken()
    {
        $token = $this->broker->generateClientToken();
        $key   = $this->broker->sessionName();

        $this->session->set($key, $token);

        return $token;
    }
}
