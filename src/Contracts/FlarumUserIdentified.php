<?php

namespace Bausch\FlarumLaravelSession\Contracts;

use Closure;
use Illuminate\Http\Request;

interface FlarumUserIdentified
{
    /**
     * Handle an authenticated Flarum user.
     *
     * @return mixed
     */
    public function __invoke(object $flarum_user, Request $request, Closure $next);
}
