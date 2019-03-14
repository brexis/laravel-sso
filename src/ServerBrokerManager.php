<?php

namespace Brexis\LaravelSSO;

use Brexis\LaravelSSO\Exceptions\InvalidSessionIdException;
use Illuminate\Http\Request;

/**
 * Class ServerBrokerManager
 */
class ServerBrokerManager
{
    /**
     * @var Brexis\LaravelSSO\Encription
     */
    protected $encription;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->encription = new Encription;
    }
    /**
     * Return broker model
     *
     * @return mixed
     * @throw \Brexis\LaravelSSO\Exceptions\InvalidSessionIdException
     */
    public function brokerModel()
    {
        $class = config('laravel-sso.brokers.model');

        if (!class_exists($class)) {
            throw new InvalidSessionIdException("Class $class does not exist");
        }

        return $class;
    }

    /**
     * Find broker by id
     *
     * @param string $sid
     *
     * @return mixed
     * @throw \Brexis\LaravelSSO\Exceptions\InvalidSessionIdException
     */
    public function findBrokerById($id)
    {
        $class    = $this->brokerModel();
        $id_field = config('laravel-sso.brokers.id_field');
        $model    = $class::where($id_field, $id)->first();

        if (!$model) {
            throw new InvalidSessionIdException("Model $class with $id_field:$id not found");
        }

        return $model;
    }

    /**
     * Find broker secret
     *
     * @param string $sid
     *
     * @return string
     * @throw \Brexis\LaravelSSO\Exceptions\InvalidSessionIdException
     */
    public function findBrokerSecret($model)
    {
        $secret_field = config('laravel-sso.brokers.secret_field');

        return $model->$secret_field;
    }

    /**
     * Validate broker session id
     *
     * @param string $sid
     *
     * @return string
     * @throw \Brexis\LaravelSSO\Exceptions\InvalidSessionIdException
     */
    public function validateBrokerSessionId($sid)
    {
        list($broker_id, $token) = $this->getBrokerInfoFromSessionId($sid);

        if ($this->generateSessionId($broker_id, $token) != $sid) {
            throw new InvalidSessionIdException('Checksum failed: Client IP address may have changed');
        }

        return $broker_id;
    }

    /**
     * Generate session id
     *
     * @param string $broker_id
     * @param string $token
     *
     * @return string
     */
    public function generateSessionId($broker_id, $token)
    {
        $model  = $this->findBrokerById($broker_id);
        $secret = $this->findBrokerSecret($model);
        $checksum = $this->encription->generateChecksum(
            'session', $token, $secret
        );

        return "SSO-{$broker_id}-{$token}-$checksum";
    }

    /**
     * Generate attach checksum
     *
     * @param string $broker_id
     * @param string $token
     *
     * @return string
     */
    public function generateAttachChecksum($broker_id, $token)
    {
        $model  = $this->findBrokerById($broker_id);
        $secret = $this->findBrokerSecret($model);

        return $this->encription->generateChecksum('attach', $token, $secret);
    }

    /**
     * Return broker info from sid
     *
     * @param string $sid
     * @return array
     */
    public function getBrokerInfoFromSessionId($sid)
    {
        if (!preg_match('/^SSO-(\w*+)-(\w*+)-([a-z0-9]*+)$/', $sid, $matches)) {
            throw new InvalidSessionIdException('Invalid session id');
        }

        array_shift($matches);

        return $matches;
    }

    /**
     * Retrieve broker session id from request
     *
     * @return string
     */
    public function getBrokerSessionId($request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            $token = $request->input('access_token');
        }

        if (!$token) {
            $token = $request->input('sso_session');
        }

        return $token;
    }

    /**
     * Return broker model from Http Request
     *
     * @param Illuminate\Http\Request $request
     *
     * @return mixed
     */
    public function getBrokerFromRequest(Request $request)
    {
        $sid = $this->getBrokerSessionId($request);
        list($broker_id) = $this->getBrokerInfoFromSessionId($sid);

        return $this->findBrokerById($broker_id);
    }
}
