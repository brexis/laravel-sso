<?php

namespace Brexis\LaravelSSO\Http\Middleware;

use Closure;
use Brexis\LaravelSSO\ServerBrokerManager;
use Brexis\LaravelSSO\Exceptions\InvalidSessionIdException;
use Brexis\LaravelSSO\Exceptions\InvalidClientException;

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
        try {
            $sid = $this->broker->getBrokerSessionId($request);
    
            $this->broker->validateBrokerSessionId($sid);
    
            return $next($request);
        } catch(InvalidClientException $e) {
            return response()->json([
                'code' => 'invalid_client_id',
                'message' => 'Invalid client id.'
            ], 403);
        } catch(InvalidSessionIdException $e) {
            return response()->json([
                'code' => 'invalid_session_id',
                'message' => $e->getMessage()
            ], 403);
        }
    }
}
