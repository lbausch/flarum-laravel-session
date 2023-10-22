<?php

namespace Bausch\FlarumLaravelSession;

use Bausch\FlarumLaravelSession\Contracts\FlarumUserIdentified;
use Closure;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Session\FileSessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class FlarumSessionMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // If the user is already authenticated, continue
        if (Auth::check()) {
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

        // Try to get access_token
        $access_token = $session_store->get('access_token', false);

        // Redirect if no access_token was found
        if (!$access_token) {
            return redirect(Config::get('flarum.url'));
        }

        // Try to get token data with user id
        $token_data = $flarum_user = DB::connection(Config::get('flarum.db_connection'))->table('access_tokens')->where('token', $access_token)->first();

        // Redirect if no token was found
        if (!$token_data || !$token_data->user_id) {
            return redirect(Config::get('flarum.url'));
        }

        $user_id = $token_data->user_id;

        // Get Flarum user from Flarum database
        $flarum_user = DB::connection(Config::get('flarum.db_connection'))->table('users')->find($user_id);

        // Abort if no Flarum user is present in database
        if (!$flarum_user) {
            abort(403);
        }

        // Get Handler for handling Flarum user
        $handler = Container::getInstance()->make(FlarumUserIdentified::class);

        // Execute handler
        return $handler($flarum_user, $request, $next);
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
}
