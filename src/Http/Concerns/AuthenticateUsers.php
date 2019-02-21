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
            $credentials = json_encode($this->sessionCredentials($request));

            // TODO Manage to use remember $request->has('remember')
            $this->session->setUserData($sid, $credentials);

            return true;
        }

        return false;
    }

    protected function attemptLogin(Request $request)
    {
        return $this->guard()->once(
            $this->loginCredentials($request)
        );
    }

    protected function loginCredentials(Request $request)
    {
        return $request->only($this->username($request), 'password');
    }

    protected function username($request)
    {
        return $request->input('login', 'email');
    }

    protected function sessionCredentials(Request $request)
    {
        $field = $this->username($request);
        $value = $request->input($field);

        return [$field => $value];
    }

    protected function guard()
    {
        return Auth::guard();
    }

    protected function userInfo($user)
    {
        $closure = config('laravel-sso.user_info');

        if (is_callable($closure)) {
            return $closure($user);
        }

        return $user->toArray();
    }
}
