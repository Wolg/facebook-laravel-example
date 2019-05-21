<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\FacebookException;
use App\Http\Controllers\Controller;
use App\User;
use Facebook\GraphNodes\GraphUser;
use App\Traits\FacebookTrait;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class FacebookController extends Controller
{
    use AuthenticatesUsers, FacebookTrait;

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

    /**
     * @return \Illuminate\Http\RedirectResponse
     * @throws FacebookException
     */
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
        }
        try {
            $fb = resolve('Facebook\SDK');
            $response = $fb->get('/me?fields=id,name,email,picture', $accessToken);
            $me = $response->getGraphUser();
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            throw new FacebookException();
        }
        $user = User::where('fb_id', '=', $me->getId())->first();
        if (!$user) {
            $user = $this->register($accessToken, $me);
        } else {
            $user->fb_token = $accessToken;
            $user->save();
        }
        Auth::login($user);
        return Redirect::back();
    }

    /**
     * @param $token
     * @param GraphUser $user
     * @return mixed
     */
    private function register($token, GraphUser $user)
    {
        return User::create([
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'fb_id' => $user->getId(),
            'fb_token' => $token,
            'picture' => $user->getPicture()->getUrl()
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        $this->deactivateUser($user);
        Auth::logout();
        return Redirect::back();
    }

    /**
     * @param Request $request
     */
    public function deAuthCallback(Request $request)
    {
        $data = $this->parse_signed_request($request->input('signed_request'));
        $facebookId = $data['user_id'];
        $user = User::where('fb_id', '=', $facebookId)->first();
        $this->deactivateUser($user);
        return new Response();
    }

    /**
     * @param User $user
     * @return bool
     */
    private function deactivateUser(User $user)
    {
        $user->fb_token = '';
        $user->is_active = false;
        return $user->save();
    }
}
