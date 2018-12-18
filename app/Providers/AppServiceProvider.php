<?php

namespace App\Providers;

use App\Model\UserToken;
use App\Observers\TokenObserver;
use Illuminate\Support\ServiceProvider;
use DB;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
		UserToken::observe(TokenObserver::class); //token模型中注册观察者
		/*
	    DB::listen(function($sql) {
			dump($sql);
	    });*/
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
