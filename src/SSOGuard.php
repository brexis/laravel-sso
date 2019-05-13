<?php

namespace Brexis\LaravelSSO;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
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
     * The request instance.
     *
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * Create a new authentication guard.
     *
     * @param  \Illuminate\Contracts\Auth\UserProvider  $provider
     * @param  \Brexis\LaravelSSO\ClientBrokerManager $broker
     * @param  \Symfony\Component\HttpFoundation\Request|null  $request
     * @return void
     */
    public function __construct(UserProvider $provider,
                                ClientBrokerManager $broker,
                                Request $request = null)
    {
        $this->provider = $provider;
        $this->broker = $broker;
        $this->request = $request;
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

        if ($payload = $this->broker->profile($this->request)) {
            $this->user = $this->loginFromPayload($payload);
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

        if ($payload = $this->broker->login($login_params, $this->request)) {
            $user = $this->loginFromPayload($payload);

            if ($user) {
                $this->fireLoginSucceededEvent($user);
            }

            return $user;
        }

        $this->fireLoginFailedEvent($credentials);

        return false;
    }

    /**
     * Log a user using the payload.
     *
     * @param  array $payload
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    public function loginFromPayload($payload)
    {
        $this->user = $this->retrieveFromPayload($payload);

        $this->updatePayload($payload);

        if ($this->user) {
            $this->fireAuthenticatedEvent($this->user);
        }

        return $this->user;
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

        $user = $this->provider->retrieveByCredentials([
            $username => $payload[$username]
        ]);

        if (empty($user)) {
            try {
                $ssoConfig = include(config_path('laravel-sso.php'));
            } catch(\ErrorException $ex) {
                $ssoConfig = [];
            }
            
            $closure = array_key_exists('register_user', $ssoConfig) ? $ssoConfig["register_user"] : '';

            if (is_callable($closure)) {
                $closure($payload);
                $user = $this->provider->retrieveByCredentials([
                    $username => $payload[$username]
                ]);
            }
        }

        return $user;
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
     * Set the current request instance.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Get the event dispatcher instance.
     *
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    public function getDispatcher()
    {
        return $this->events;
    }

    /**
     * Set the event dispatcher instance.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function setDispatcher($events)
    {
        $this->events = $events;
    }

    /**
     * Logout user.
     *
     * @return void
     */
    public function logout()
    {
        $user = $this->user();

        if ($this->broker->logout($this->request)) {
            $this->fireLogoutEvent($user);

            $this->user = null;
        }
    }

    /**
     * Fire the login success event if the dispatcher is set.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     *
     * @return void
     */
    protected function fireLoginSucceededEvent($user)
    {
        if (isset($this->events)) {
            $this->events->dispatch(new Events\LoginSucceeded($user, $this->request));
        }
    }

    /**
     * Fire the authenticated event with the arguments.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     *
     * @return void
     */
    protected function fireAuthenticatedEvent($user)
    {
        if (isset($this->events)) {
            $this->events->dispatch(new Events\Authenticated(
                $user, $this->request
            ));
        }
    }

    /**
     * Fire the failed authentication attempt event with the given arguments.
     *
     * @param array $credentials
     *
     * @return void
     */
    protected function fireLoginFailedEvent($credentials)
    {
        if (isset($this->events)) {
            $this->events->dispatch(new Events\LoginFailed($credentials, $this->request));
        }
    }

    /**
     * Fire the logout event with the given arguments.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     *
     * @return void
     */
    protected function fireLogoutEvent($user)
    {
        if (isset($this->events)) {
            $this->events->dispatch(new Events\Logout($user));
        }
    }
}
