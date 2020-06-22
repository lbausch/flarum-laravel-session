<?php

return [
    /*
     * Flarum url
     */
    'url' => env('FLARUM_URL'),

    /*
     * Model which is authenticatable
     */
    'model' => App\User::class,

    /*
     * Flarum session configuration
     */
    'session' => [
        /*
         * Name of the Flarum session cookie
         */
        'cookie' => env('FLARUM_SESSION_COOKIE', 'flarum_session'),

        /*
         * Absolute path to the session directory of Flarum
         */
        'path' => base_path('flarum/storage/sessions'),
    ],

    /*
     * Flarum database connection as defined in config/database.php
     */
    'db_connection' => env('FLARUM_DB_CONNECTION', 'flarum'),

    /*
     * Attributes to update upon successful authentication: Flarum user => local user
     */
    'update_attributes' => [
        'username' => 'username',
        'id' => 'flarum_id',
        'email' => 'email',
    ],
];
