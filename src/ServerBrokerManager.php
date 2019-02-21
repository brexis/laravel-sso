<?php

namespace Brexis\LaravelSSO;

use Brexis\LaravelSSO\Exceptions\InvalidSessionIdException;

/**
 * Class ServerBrokerManager
 */
class ServerBrokerManager
{
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
        $matches = null;

        if (!preg_match('/^SSO-(\w*+)-(\w*+)-([a-z0-9]*+)$/', $sid, $matches)) {
            throw new InvalidSessionIdException('Invalid session id');
        }

        $broker_id = $matches[1];
        $token = $matches[2];

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
        // TODO replace checksum by encription

        return "SSO-{$broker_id}-{$token}-" . hash('sha256', 'session' . $token . $secret);
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
        // TODO Replace checksum by encription

        return hash('sha256', 'attach' . $token . $secret);
    }

    /**
     * Return boker session id
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
}
