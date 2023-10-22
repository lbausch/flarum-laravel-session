<?php

namespace Bausch\FlarumLaravelSession\Actions;

use Bausch\FlarumLaravelSession\Contracts\FlarumUserIdentified;
use Bausch\FlarumLaravelSession\FlarumLaravelSession;
use Closure;
use Illuminate\Container\Container;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class HandleIdentifiedUser implements FlarumUserIdentified
{
    public function __invoke(object $flarum_user, Request $request, Closure $next)
    {
        // Find the corresponding local user
        $user = $this->getUser()->where('flarum_id', $flarum_user->id)->first();

        // Create or update the corresponding local user
        $user = $this->createOrUpdateUser($user, $flarum_user);

        // Login the local user and remember him
        Auth::login($user, $remember = true);

        return $next($request);
    }

    /**
     * Create or update User.
     */
    protected function createOrUpdateUser(?User $user, object $flarum_user): User
    {
        // Get a user instance
        if (null === $user) {
            $user = $this->getUser();
        }

        $user = $this->updateAttributes($user, $flarum_user);

        // Save user
        if ($user->isDirty()) {
            $user->save();
        }

        // Return user
        return $user;
    }

    /**
     * Update attributes.
     */
    protected function updateAttributes(?User $user, object $flarum_user): User
    {
        // Attributes to update: Flarum user => local user
        $update_attributes = [
            'username' => 'username',
            'id' => 'flarum_id',
            'email' => 'email',
        ];

        // Update attributes
        foreach ($update_attributes as $flarum_attribute => $local_attribute) {
            $user->{$local_attribute} = $flarum_user->{$flarum_attribute};
        }

        if ($user->isDirty()) {
            // Set a random password
            $user->password = bcrypt(Str::random(30));
        }

        return $user;
    }

    /**
     * Get user instance.
     */
    protected function getUser(): User
    {
        return Container::getInstance()->make(FlarumLaravelSession::userModel());
    }
}
