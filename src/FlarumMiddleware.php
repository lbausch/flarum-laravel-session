<?php

namespace Bausch\FlarumLaravelSession;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Session\FileSessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FlarumMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // If the user is already authenticated, continue
        if (Auth::check()) {
            // dd('asf');
            return $next($request);
        }

        // Get content of Flarum session cookie
        $flarum_session_id = $request->cookie(Config::get('flarum.session.cookie', false));

        // Abort if no session id could be found
        if (!$flarum_session_id) {
            abort(403);
        }

        // Create session store
        $session_store = new Store('flarum-laravel-session', $this->getFileSessionHandler(), $flarum_session_id);

        // Start session
        $session_store->start();

        // Try to get user id
        $user_id = $session_store->get('user_id', false);

        // Redirect if no user id was found
        if (!$user_id) {
            return redirect(Config::get('flarum.url'));
        }

        // Get Flarum user from Flarum database
        $flarum_user = DB::connection(Config::get('flarum.db_connection'))->table('users')->find($user_id);

        // Abort if no Flarum user is present in database
        if (!$flarum_user) {
            abort(403);
        }

        // Find the corresponding local user
        $user = $this->getUser()->where('flarum_id', $flarum_user->id)->first();

        // Create or update the corresponding local user
        $user = $this->createOrUpdateUser($user, $flarum_user);

        // Login the local user and remember him
        Auth::login($user, true);

        return $next($request);
    }

    /**
     * Get FileSessionHandler.
     */
    protected function getFileSessionHandler(): FileSessionHandler
    {
        // Create filesystem
        $filesystem = Container::getInstance()->make(Filesystem::class);

        // Get path to session files
        $session_path = Config::get('flarum.session.path');

        // Session lifetime in minutes
        $lifetime_minutes = 120;

        return new FileSessionHandler(
            $filesystem,
            $session_path,
            $lifetime_minutes
        );
    }

    /**
     * Create or update User.
     */
    protected function createOrUpdateUser(?User $user, object $flarum_user): User
    {
        // Get a user instance
        if (is_null($user)) {
            $user = $this->getUser();
        }

        // Attributes to update: Flarum user => local user
        $update_attributes = Config::get('flarum.update_attributes', []);

        // Update attributes
        foreach ($update_attributes as $flarum_attribute => $local_attribute) {
            $user->{$local_attribute} = $flarum_user->{$flarum_attribute};
        }

        // Set a random password
        $user->password = bcrypt(Str::random(30));

        // Save user
        if ($user->isDirty()) {
            $user->save();
        }

        // Return user
        return $user;
    }

    /**
     * Get User instance.
     */
    protected function getUser(): User
    {
        return Container::getInstance()->make(Config::get('flarum.model'));
    }
}
