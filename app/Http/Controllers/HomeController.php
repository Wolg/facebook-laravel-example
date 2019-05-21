<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\FacebookController;

class HomeController extends Controller
{
    public function index()
    {
        $fb = resolve('Facebook\SDK');
        $helper = $fb->getRedirectLoginHelper();
        $loginUrl = $helper->getLoginUrl(url('/auth/facebook/callback'), config('services.facebook.permissions'));

        return view('welcome', [
            'loginUrl' => $loginUrl
        ]);
    }
}
