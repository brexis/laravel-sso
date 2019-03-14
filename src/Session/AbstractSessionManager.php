<?php

namespace Brexis\LaravelSSO\Session;

abstract class AbstractSessionManager
{
    /**
     * Return The session store
     */
    abstract protected function store();

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
        if (($forever || $this->isTTLForever()) && is_callable([$this->store(), 'forever'])) {
            $this->store()->forever($key, $value);
        } else {
            $ttl = $this->getSessionTTL();
            $this->store()->put($key, $value, $ttl);
        }
    }

    /**
     * Return session value of the key $key
     *
     * @return string $key
     * @return mixed $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->store()->get($key, $default);
    }

    /**
     * Delete session value of the key $key
     */
    public function forget($key)
    {
        $this->store()->forget($key);
    }
}
