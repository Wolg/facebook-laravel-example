<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\FacebookException;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class FacebookController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function callback()
    {
        $fb = resolve('Facebook\SDK');
        $helper = $fb->getRedirectLoginHelper();

        try {
            $accessToken = $helper->getAccessToken();
        } catch(Facebook\Exceptions\FacebookResponseException $e) {
            throw new FacebookException();
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
            throw new FacebookException();
        }

        if (! isset($accessToken)) {
            throw new FacebookException();
        }

        $oAuth2Client = $fb->getOAuth2Client();
        $tokenMetadata = $oAuth2Client->debugToken($accessToken);

        try {
            $tokenMetadata->validateAppId(config('services.facebook.id'));
            $tokenMetadata->validateExpiration();
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
            throw new FacebookException();
        }

        if (!$accessToken->isLongLived()) {
            // Exchanges a short-lived access token for a long-lived one
            try {
                $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
            } catch (Facebook\Exceptions\FacebookSDKException $e) {
                throw new FacebookException();
            }

            $this->register($accessToken);
        }
        $user = User::where('fb_token', '=', $accessToken)->first();
        if (!$user) {
            try {
                $user = $this->register($accessToken);
            } catch (Facebook\Exceptions\FacebookSDKException $e) {
                throw new FacebookException();
            }
        }
        Auth::login($user);
        return Redirect::back();
    }

    /**
     * @param $token
     * @return mixed
     */
    private function register($token)
    {
        $fb = resolve('Facebook\SDK');
        $response = $fb->get('/me?fields=id,name,email,picture', $token);
        $me = $response->getGraphUser();
        return User::create([
            'name' => $me->getName(),
            'email' => $me->getEmail(),
            'fb_token' => $token,
            'picture' => $me->getPicture()->getUrl()
        ]);
    }
}
