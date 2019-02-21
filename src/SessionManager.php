<?php

namespace Brexis\LaravelSSO;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class SessionManager
{
    /**
     * Return the session configuration ttl
     * @return int
     */
    protected function getSessionTTL()
    {
        return config('laravel-sso.session_ttl');
    }

    /**
     * Check if session ttl is forever, means if it value is null
     * @return bool
     */
    protected function isTTLForever()
    {
        return is_null($this->getSessionTTL());
    }

    /**
     * Set session value in the cache
     * @param $key string
     * @param $value string
     * @param $forever bool
     */
    public function set($key, $value, $forever = false)
    {
        if ($forever || $this->isTTLForever()) {
            Cache::forever($key, $value);
        } else {
            $ttl = $this->getSessionTTL();
            Cache::put($key, $value, $ttl);
        }
    }

    /**
     * Return session value of the key $key
     * @return string
     */
    public function get($key, $default = null)
    {
        return Cache::get($key, $default);
    }

    /**
     * Delete session value of the key $key
     */
    public function forget($key)
    {
        Cache::forget($key);
    }

    /**
     * Set user session data
     */
    public function setUserData($sid, $value)
    {
        $id = $this->get($sid);

        Session::setId($id);
        Session::start();

        Session::put('sso_user', $value);
        Session::save();
    }

    /**
     * Retrieve user session data
     * 
     * @return string
     */
    public function getUserData($sid)
    {
        $id = $this->get($sid);

        Session::setId($id);
        Session::start();

        return Session::get('sso_user');
    }

    /**
     * Start a new session by resetting the session value
     */
    public function start($sid)
    {
        $id = Session::getId();

        $this->set($sid, $id);
    }
}
