<?php

namespace App\Providers;

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
        $fb = new \Facebook\Facebook([
            'app_id' => config('services.facebook.id'),
            'app_secret' => config('services.facebook.secret'),
            'default_graph_version' => 'v3.3'
        ]);
        $this->app->instance('Facebook\SDK', $fb);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
