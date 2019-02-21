<?php

namespace Brexis\LaravelSSO;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\Authenticatable;

class SSOGuard implements Guard
{
    /**
     * The currently authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    protected $user;

    /**
     * The user provider implementation.
     *
     * @var \Illuminate\Contracts\Auth\UserProvider
     */
    protected $provider;

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
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check()
    {
        return ! is_null($this->user());
    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest()
    {
        return ! $this->check();
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

        if ($user_data = $this->broker->profile()) {
            $username = config('laravel-sso.broker_client_username', 'email');

            if (!array_key_exists($username, $user_data)) {
                return false;
            }

            $this->user = $this->provider->retrieveByCredentials([
                $username => $user_data[$username]
            ]);
        }

        return $this->user;
    }


    /**
     * Get the ID for the currently authenticated user.
     *
     * @return int|null
     */
    public function id()
    {
        if ($this->user()) {
            return $this->user()->getAuthIdentifier();
        }
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

        if ($this->broker->login($login_params)) {
            $user = $this->provider->retrieveByCredentials($credentials);

            $this->user = $user;
            return true;
        }

        return false;
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
     * Set the current user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    public function setUser(Authenticatable $user)
    {
        $this->user = $user;

        return $this;
    }
}
