<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
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
