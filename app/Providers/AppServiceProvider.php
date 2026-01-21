<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
        
        DB::listen(function () {
            try {
                $pdo = DB::connection()->getPdo();
            } catch (\Exception $e) {
                DB::disconnect('sqlsrv');
                DB::reconnect('sqlsrv');
            }
        });
    }
}
