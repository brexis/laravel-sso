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
     * Broker id for client configuration. Must be null on SSO Server. Must
     * mactch any word [a-zA-Z0-9_]
     */
    'broker_client_id' => null,

    /**
     * Broker secret for client configuration. Must be null on SSO Server
     */
    'broker_client_secret' => null,

    /**
     * Broker client unique username
     */
    'broker_client_username' => 'email',

    /**
     * The server Url. Required for clients.
     */
    'broker_server_url' => '',

    /**
     * The return Url. Required for clients.
     */
    'broker_return_url' => true,

    /**
     * Session live time Default to 60 minutes. Set to null to store forever
     */
    'session_ttl' => 60,

    /**
     * Closure that return the user info from server. This function allows you
     * to return additional payload data to the clients. By default, the user
     * attributes are returned by calling $user->toArray().
     * Eg. 'user_info' => function($user, $broker, $request) {
     *      $payload = $user->toArray();
     *      $payload['roles'] = $user->getRolesByApp($broker->id);
     *
     *      return $payload
     * }
     */
    'user_info' => null,

    /**
     * Closure that is called after a user is authenticated. Used for
     * additional verification, for exemple if you don't want to allow
     * unverified users. This function should return a boolean.
     * Eg. 'after_authenticating' => function($user, $request) {
     *      return $user->verified;
     * }
     */
    'after_authenticating' => null,

    /**
     * Enable debug mode
     */
    'debug' => false,

    /**
     * Closure that save the user in the client local database.
     * Eg. 'user_create_strategy' => function ($data) {
     *    return \App\Models\User::create([
     *        'username' => $data['username'],
     *        'email' => $data['email'],
     *        'admin' => $data['admin'],
     *        'password' => '',
     *    ]);
     * }
     */
    'user_create_strategy' => null,

    /**
     * Commands are customs additionals methods that could be called
     * from the client. For exemple if you want to check the authenticated
     * user role.
     */
    'commands' => [
        /**
         * Should return an array
         * 'hasRole' => function($user, $broker, $request) {
         *     $role = $request->input('role');
         *     $success = $user->roles->contains($role);
         *     return ['success' => $success];
         * }
         */
    ]
];
