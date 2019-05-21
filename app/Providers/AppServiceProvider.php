<?php

namespace App\Providers;

use Facebook\PersistentData\PersistentDataInterface;
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
            'default_graph_version' => 'v3.3',
            'persistent_data_handler' => new class() implements PersistentDataInterface {
                private $sessionPrefix = 'FB_';

                public function get($key)
                {
                    return \Session::get($this->sessionPrefix . $key);
                }

                public function set($key, $value)
                {
                    \Session::put($this->sessionPrefix . $key, $value);
                }
            },
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
