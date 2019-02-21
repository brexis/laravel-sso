<?php

namespace Brexis\LaravelSSO;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Support\Traits\Macroable;

class SSOGuard implements Guard
{
    use GuardHelpers, Macroable;

    /**
     * The user provider implementation.
     *
     * @var \Brexis\LaravelSSO\ClientBrokerManager
     */
    protected $broker;

    /**
     * Create a new authentication guard.
     *
     * @param  \Illuminate\Contracts\Auth\UserProvider  $provider
     * @return void
     */
    public function __construct(UserProvider $provider, ClientBrokerManager $broker)
    {
        $this->provider = $provider;
        $this->broker = $broker;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        if (! is_null($this->user)) {
            return $this->user;
        }

        if ($payload = $this->broker->profile()) {
            $this->user = $this->retrieveFromPayload($payload);

            $this->updatePayload($payload);
        }

        return $this->user;
    }

    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param  array  $credentials
     * @param  bool   $remember
     * @return bool
     */
    public function attempt(array $credentials = [], $remember = false)
    {
        $login_params = $credentials;
        if ($remember) {
            $login_params['remember'] = true;
        }

        if ($payload = $this->broker->login($login_params)) {
            $this->user = $this->retrieveFromPayload($payload);

            $this->updatePayload($payload);

            return $this->user;
        }

        return false;
    }

    /**
     * Retrieve user from payload
     *
     * @param mixed $payload
     * @return mixed
     */
    protected function retrieveFromPayload($payload)
    {
        $username = config('laravel-sso.broker_client_username', 'email');

        if (!array_key_exists($username, $payload)) {
            return false;
        }

        return $this->provider->retrieveByCredentials([
            $username => $payload[$username]
        ]);
    }

    /**
     * Update user payload
     *
     * @param array $payload
     */
    protected function updatePayload($payload)
    {
        if ($this->user && method_exists($this->user, 'setPayload')) {
            $this->user->setPayload($payload);
        }
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        return ! is_null($user);
    }

    /**
     * Logout user.
     *
     * @return void
     */
    public function logout()
    {
        if ($this->broker->logout()) {
            $this->user = null;
        }
    }
}
