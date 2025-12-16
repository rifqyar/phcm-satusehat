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
        if ($_SERVER['REMOTE_ADDR'] == '::1' || $_SERVER['REMOTE_ADDR'] == '103.234.195.158') {
            if (Session::has('is_logged_in')) {
                config(['session.lifetime' => 1440]);
                return $next($request);
            } else {
                Session::invalidate();
                Session::regenerateToken();
                return redirect('login');
            }
        } else {
            // 1. Kalau sudah login di Laravel → lanjut
            if (session()->has('ci_synced')) {
                return $next($request);
            }

            // 2. Jangan cek CI untuk request asset
            if ($request->is('css/*', 'js/*', 'images/*', 'favicon.ico')) {
                return $next($request);
            }

            // 3. Coba sinkron dari CI (1x saja)
            try {
                $loggedIn = ci_session('sdh_masuk_simrs');

                if ($loggedIn === true) {
                    session()->put('ci_synced', true);
                    session()->save();
                    return $next($request);
                }
            } catch (\Throwable $e) {
                logger()->warning('CI auth bridge failed', [
                    'error' => $e->getMessage()
                ]);
            }

            // 4. GAGAL → redirect
            return redirect('https://sim.phcm.co.id/simrs');
        }
    }
}
