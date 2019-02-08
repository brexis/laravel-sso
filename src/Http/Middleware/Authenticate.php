<?php

namespace Brexis\LaravelSSO\Http\Middleware;

use Closure;
use Brexis\LaravelSSO\BrokerManager;
use Brexis\LaravelSSO\SessionManager;
use Illuminate\Support\Facades\Auth;

class Authenticate
{
    protected $broker;

    protected $session;

    public function __construct(BrokerManager $broker, SessionManager $session)
    {
        $this->broker = $broker;
        $this->session = $session;
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
        $guard = $guard ?: Auth::guard();

        $sid = $this->broker->getBrokerSessionId($request);
        $this->broker->validateBrokerSessionId($sid);

        if ($this->check($guard, $sid)) {
            return $next($request);
        }

        throw new \Exception('Unauthorized');
    }

    protected function check($guard, $sid)
    {
        $attrs = json_decode($this->session->get($sid), true);

        if (!empty($attrs)) {
            return $guard->getProvider()->retrieveByCredentials($attrs);
        }

        return false;
    }
}
