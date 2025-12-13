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
            if (ci_session('erm_sensus_medan_logged_in') !== true) {
                return redirect('http://10.1.19.22/login');
            }

            return $next($request);
        }
    }
}
