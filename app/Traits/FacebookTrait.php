<?php
namespace App\Traits;

use App\Exceptions\FacebookException;
use Illuminate\Support\Facades\Log;

trait FacebookTrait
{
    /**
     * @return mixed
     * @throws FacebookException
     */
    protected function getAccessToken()
    {
        $helper = $this->fb->getRedirectLoginHelper();
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

        $oAuth2Client = $this->fb->getOAuth2Client();
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
        return $accessToken;
    }

    /**
     * @param $signedRequest
     * @return mixed|null
     */
    protected function parse_signed_request($signedRequest) {
        list($encodedSig, $payload) = explode('.', $signedRequest, 2);

        $secret = config('services.facebook.secret'); // Use your app secret here

        // decode the data
        $sig = $this->base64_url_decode($encodedSig);
        $data = json_decode($this->base64_url_decode($payload), true);

        // confirm the signature
        $expectedSig = hash_hmac('sha256', $payload, $secret, $raw = true);
        if ($sig !== $expectedSig) {
            Log::error('Bad Signed JSON signature!');
            return null;
        }

        return $data;
    }

    /**
     * @param $input
     * @return bool|string
     */
    protected function base64_url_decode($input) {
        return base64_decode(strtr($input, '-_', '+/'));
    }
}