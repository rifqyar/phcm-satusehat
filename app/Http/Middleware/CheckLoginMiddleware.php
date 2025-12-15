<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Session;

class CheckLoginMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($_SERVER['REMOTE_ADDR'] == '::1') {
            if (Session::has('is_logged_in')) {
                config(['session.lifetime' => 1440]);
                return $next($request);
            } else {
                Session::invalidate();
                Session::regenerateToken();
                return redirect('login');
            }
        } else {
            if (ci_session('sdh_masuk_simrs') !== true) {
                return redirect('https://sim.phcm.co.id/simrs/login');
            }

            return $next($request);
        }
    }
}
