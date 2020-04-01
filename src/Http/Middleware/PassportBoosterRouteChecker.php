<?php

namespace BGS\PassportBooster\Http\Middleware;

use BGS\PassportBooster\PassportBooster;
use Closure;
use Illuminate\Support\Facades\Config;

class PassportBoosterRouteChecker
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $auth_guard = 'api')
    {
        if (PassportBooster::isMultiGuard()) {
            $guard = $request->get('guard', $auth_guard);

            if ($guard) {
                PassportBooster::setGuard($guard);
                $guard = config('auth.guards.' . $guard);
                Config::set('auth.guards.api.provider', $guard['provider']);
            } else {
                abort(401, 'Invalid Request');
            }
        }

        return $next($request);
    }
}
