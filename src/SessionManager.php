<?php

namespace Brexis\LaravelSSO;

use Illuminate\Support\Facades\Cache;

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
     * Start a new session by resetting the session value
     */
    public function start($sid)
    {
        $this->set($sid, '{}');
    }
}
