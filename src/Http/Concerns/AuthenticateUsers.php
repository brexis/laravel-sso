<?php

namespace Brexis\LaravelSSO\Http\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait AuthenticateUsers
{
    protected function authenticate(Request $request, $broker)
    {
        if ($this->attemptLogin($request)) {
            $sid = $this->broker->getBrokerSessionId($request);
            $session_value = json_encode([
                $this->username() => $this->sessionValue($request)
            ]);

            $this->session->set($sid, $session_value, $request->has('remember'));

            return true;
        }

        return false;
    }

    protected function attemptLogin(Request $request)
    {
        return $this->guard()->once(
            $this->credentials($request)
        );
    }

    protected function credentials(Request $request)
    {
        return $request->only($this->username(), 'password');
    }

    protected function username()
    {
        return 'email';
    }

    protected function sessionValue(Request $request)
    {
        return $request->input($this->username());
    }

    protected function guard()
    {
        return Auth::guard();
    }

    protected function userInfo($username)
    {
        return $this->guard()
                    ->getProvider()
                    ->retrieveByCredentials([
                        $this->username() => $username
                    ])
                    ->toArray();
    }
}
