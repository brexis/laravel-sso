<?php

namespace Brexis\LaravelSSO\Http\Controllers;

use Brexis\LaravelSSO\BrokerManager;
use Brexis\LaravelSSO\Http\Middleware\ValidateBroker;
use Brexis\LaravelSSO\Http\Concerns\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServerController extends Controller
{
    use AuthenticatesUsers;

    protected $broker;

    protected $session;

    protected $return_type = null;

    public function __construc(BrokerManager $broker, SessionManager $session)
    {
        $this->middleware(ValidateBroker::class)->except('attach');

        $this->broker = $broker;
        $this->session = $session;
    }

    public function attach(Request $request)
    {
        $this->detectReturnType($request);

        $broker_id = $request->input('broker');
        $token     = $request->input('token');
        $checksum  = $request->input('checksum');

        if (!$this->return_type) {
            throw new \Exception('No return url specified');
        }

        $gen_checksum = $this->broker->generateAttachChecksum($broker_id, $token);

        if (!$checksum || $checksum !== $gen_checksum) {
            throw new \Exception('Invalid checksum');
        }

        $sid = $this->generateSessionId($broker_id, $token);
        $this->session->start($sid);

        return $this->outputAttachSuccess();
    }

    public function login(Request $request)
    {
        if ($this->authenticate($request, $this)) {
            return response()->json(
                $this->userInfo(
                    $this->sessionValue()
                )
            );
        }
    }

    protected function detectReturnType(Request $request)
    {
        if ($request->has('return_url')) {
            $this->return_type = 'redirect';
        } elseif ($request->has('callback')) {
            $this->return_type = 'jsonp';
        } elseif ($request->accepts('image/*')) {
            $this->return_type = 'image';
        } elseif ($request->acceptsJson()) {
            $this->return_type = 'json';
        }
    }

    protected function outputAttachSuccess($request)
    {
        $callback = $request->input('callback');
        $return_url = $request->input('return_url');

        if ($this->return_type === 'image') {
            return $this->outputImage();
        }

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

    /**
     * Output a 1x1px transparent image
     */
    protected function outputImage()
    {
        return response(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQ'
            . 'MAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZg'
            . 'AAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII='));
    }
}
