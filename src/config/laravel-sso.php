<?php

return [

    /**
     * Brokers configurations
     */
    'brokers' => [
        /**
         * Boker model class
         */
        'model' => 'App\Models\App',

        /**
         * Boker model id field
         */
        'id_field' => 'app_id',

        /**
         * Boker model secret field
         */
        'secret_field' => 'secret'
    ],

    /**
     * Session live time Default to 60 minutes. Set to null to store forever
     */
    'session_ttl' => 60,
];
