<?php
namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait FacebookTrait
{
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