<?php

namespace Brexis\LaravelSSO\Http\Controllers;

use Brexis\LaravelSSO\ServerBrokerManager;
use Brexis\LaravelSSO\Session\ServerSessionManager;
use Brexis\LaravelSSO\Http\Middleware\ValidateBroker;
use Brexis\LaravelSSO\Http\Middleware\ServerAuthenticate;
use Brexis\LaravelSSO\Http\Concerns\AuthenticateUsers;
use Brexis\LaravelSSO\Exceptions\NotAttachedException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller;
use Validator;

class ServerController extends Controller
{
    use AuthenticateUsers;

    protected $broker;

    protected $session;

    protected $return_type = null;

    public function __construct(ServerBrokerManager $broker, ServerSessionManager $session)
    {
        $this->middleware(ValidateBroker::class)->except('attach');
        $this->middleware(ServerAuthenticate::class)->only(['profile', 'logout']);

        $this->broker = $broker;
        $this->session = $session;
    }

    /**
     * Attach client broker to server
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function attach(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'broker' => 'required',
            'token' => 'required',
            'checksum' => 'required'
        ]);

         if ($validator->fails()) {
            return response($validator->errors() . '', 400);
        }

        $this->detectReturnType($request);

        if (!$this->return_type) {
            return response('No return url specified', 400);
        }

        $broker_id = $request->input('broker');
        $token     = $request->input('token');
        $checksum  = $request->input('checksum');

        $gen_checksum = $this->broker->generateAttachChecksum($broker_id, $token);

        if (!$checksum || $checksum !== $gen_checksum) {
            return response('Invalid checksum', 400);
        }

        $sid = $this->broker->generateSessionId($broker_id, $token);
        $this->session->start($sid);

        return $this->outputAttachSuccess($request);
    }

    /**
     * Login
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @throw Brexis\LaravelSSO\Exceptions\NotAttachedException
     */
    public function login(Request $request)
    {
        $sid = $this->broker->getBrokerSessionId($request);

        if (is_null($this->session->get($sid))) {
            throw new NotAttachedException(403, 'Client broker not attached.');
        }

        if ($this->authenticate($request, $this)) {
            $user = $this->guard()->user();

            return response()->json($this->userInfo($user, $request));
        }

        return response()->json([], 401);
    }

    /**
     * Get user profile
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function profile(Request $request)
    {
        $user = $this->guard()->user();

        return response()->json(
            $this->userInfo($user, $request)
        );
    }

    /**
     * Logout user
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $sid = $this->broker->getBrokerSessionId($request);

        $this->session->setUserData($sid, null);

        return response()->json(['success' => true]);
    }

    protected function detectReturnType(Request $request)
    {
        if ($request->has('return_url')) {
            $this->return_type = 'redirect';
        } elseif ($request->has('callback')) {
            $this->return_type = 'jsonp';
        } elseif ($request->expectsJson()) {
            $this->return_type = 'json';
        }
    }

    protected function outputAttachSuccess($request)
    {
        $callback = $request->input('callback');
        $return_url = $request->input('return_url');

        if ($this->return_type === 'json') {
            return response()->json(['success' => 'attached']);
        }

        if ($this->return_type === 'jsonp') {
            $data = json_encode(['success' => 'attached']);
            return response("$callback($data, 200)");
        }

        if ($this->return_type === 'redirect') {
            return redirect()->away($return_url);
        }
    }
}
