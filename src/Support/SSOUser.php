<?php

namespace Brexis\LaravelSSO\Support;

/**
 * SSOUser trait
 */
trait SSOUser
{
    /**
     * The sso payload data
     *
     * @var mixed
     */
    protected $sso_payload;

    /**
     * Set sso payload data
     *
     * @param mixed $payload
     */
    public function setPayload($payload)
    {
        $this->sso_payload = $payload;
    }

    /**
     * Return sso payload data
     *
     * @var mixed
     */
    public function getPayload()
    {
        return $this->sso_payload;
    }
}
