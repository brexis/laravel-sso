<?php

namespace Brexis\LaravelSSO\Events;

use Illuminate\Queue\SerializesModels;

class Authenticated
{
    use SerializesModels;

    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    public $user;

    /**
     * The authenticated user.
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
    public function __construct($user, $request = null)
    {
        $this->user = $user;
        $this->request = $request;
    }
}
