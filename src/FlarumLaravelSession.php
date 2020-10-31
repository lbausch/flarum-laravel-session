<?php

namespace Bausch\FlarumLaravelSession;

use Bausch\FlarumLaravelSession\Contracts\FlarumUserIdentified;

class FlarumLaravelSession
{
    /**
     * The user model that should be used by FlarumLaravelSession.
     *
     * @var string
     */
    public static $userModel = 'App\\Models\\User';

    /**
     * Register class that handles actions once a Flarum user has been identified.
     */
    public static function handleIdentifiedUser(string $class)
    {
        return app()->singleton(FlarumUserIdentified::class, $class);
    }

    /**
     * Get the name of the user model used by the application.
     *
     * @return string
     */
    public static function userModel()
    {
        return static::$userModel;
    }

    /**
     * Specify the user model that should be used by FlarumLaravelSession.
     *
     * @return static
     */
    public static function useUserModel(string $model)
    {
        static::$userModel = $model;

        return new static();
    }
}
