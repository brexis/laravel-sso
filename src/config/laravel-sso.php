<?php

return [

    /**
     * Brokers configurations for SSO Server
     */
    'brokers' => [
        'driver' => 'model', // Use model or array driver

        /**
         * Boker model class, required for model driver.
         */
        'model' => 'App\Models\App',

        /**
         * Boker model id field, required for model driver.
         */
        'id_field' => 'app_id',

        /**
         * Boker model secret field, required for model driver.
         */
        'secret_field' => 'secret',

        /**
         * Broker's list, required for list driver. ['id' => 'secret']
         */
        'list' => [],
    ],

    /**
     * Broker id for client configuration. Must be null on SSO Server
     */
    'broker_client_id' => null,

    /**
     * Broker secret for client configuration. Must be null on SSO Server
     */
    'broker_client_secret' => null,

    /**
     * Session live time Default to 60 minutes. Set to null to store forever
     */
    'session_ttl' => 60,
];
