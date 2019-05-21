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
     * @var Facebook
     */
    protected $fb;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->fb = resolve('Facebook\SDK');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     * @throws FacebookException
     */
    public function callback()
    {
        $accessToken = $this->getAccessToken();
        try {
            $response = $this->fb->get('/me?fields=id,name,email,picture', $accessToken);
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
        $user->deactivate();
        Auth::logout();
        return Redirect::back();
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function deAuthCallback(Request $request)
    {
        $data = $this->parse_signed_request($request->input('signed_request'));
        $facebookId = $data['user_id'];
        $user = User::where('fb_id', '=', $facebookId)->first();
        $user->deactivate();
        return new Response();
    }
}
