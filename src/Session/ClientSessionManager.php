<?php

namespace Brexis\LaravelSSO\Session;


class ClientSessionManager extends AbstractSessionManager
{
    /**
     * Use Laravel session as store
     */
    protected function store()
    {
        return app()->session;
    }
}
