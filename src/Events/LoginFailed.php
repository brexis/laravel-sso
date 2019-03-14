<?php

namespace Brexis\LaravelSSO\Events;

use Illuminate\Queue\SerializesModels;

class LoginFailed
{
    use SerializesModels;

    /**
     * The creadentials.
     *
     * @var array
     */
    public $credentials;

    /**
     * The request object.
     *
     * @var \Illuminate\Http\Request
     */
    public $request;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null $user
     * @param  \Illuminate\Http\Request $request
     * @return void
     */
    public function __construct($credentials, $request = null)
    {
        $this->credentials = $credentials;
        $this->request = $request;
    }
}
