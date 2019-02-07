<?php

namespace Brexis\LaravelSSO;
use Cache;

class BrokerManager
{
    public function brokerModel()
    {
        $class = config('laravel-sso.brokers.model');

        if (!class_exists($class)) {
            throw new \Exception("Class $class does not exist");
        }

        return $class;
    }

    public function findBrokerById($id)
    {
        $class    = $this->brokerModel();
        $id_field = config('laravel-sso.brokers.id_field');
        $model    = $class::where($id_field, $id)->first();

        if (!$model) {
            throw new \Exception("Model $class with $id_field:$id not found");
        }

        return $model;
    }

    public function findBrokerSecret($model)
    {
        $secret_field = config('laravel-sso.brokers.secret_field');

        return $model->$secret_field;
    }

    public function validateBrokerSessionId($sid)
    {
        $matches = null;

        if (!preg_match('/^SSO-(\w*+)-(\w*+)-([a-z0-9]*+)$/', $sid, $matches)) {
            throw new \Exception('Invalid session id');
        }

        $broker_id = $matches[1];
        $token = $matches[2];

        if ($this->generateSessionId($broker_id, $token) != $sid) {
            throw new \Exception('Checksum failed: Client IP address may have changed');
        }

        return $broker_id;
    }

    public function generateSessionId($broker_id, $token)
    {
        $model  = $this->findBrokerById($broker_id);
        $secret = $this->findBrokerSecret($model);

        return "SSO-{$broker_id}-{$token}-" . hash('sha256', 'session' . $token . $secret);
    }

    public function generateAttachChecksum($broker_id, $token)
    {
        $model  = $this->findBrokerById($broker_id);
        $secret = $this->findBrokerSecret($model);

        return hash('sha256', 'attach' . $token . $secret);
    }

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
