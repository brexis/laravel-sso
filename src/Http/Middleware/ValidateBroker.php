<?php

namespace Brexis\LaravelSSO\Http\Middleware;

use Closure;
use Brexis\LaravelSSO\ServerBrokerManager;

class ValidateBroker
{
    protected $broker;

    public function __construct(ServerBrokerManager $broker)
    {
        $this->broker = $broker;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $sid = $this->broker->getBrokerSessionId($request);

        $this->broker->validateBrokerSessionId($sid);

        return $next($request);
    }
}
