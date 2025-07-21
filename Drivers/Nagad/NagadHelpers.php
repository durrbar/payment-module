<?php

namespace Modules\Payment\Drivers\Nagad;

use Carbon\Carbon;
use Modules\Payment\Drivers\Nagad\Exceptions\NagadInvalidPrivateKey;
use Modules\Payment\Drivers\Nagad\Exceptions\NagadInvalidPublicKey;

trait NagadHelpers
{
    /**
     * @return string|null
     */
    public function getIp()
    {
        return request()->ip();
    }

    /**
     * @param  int  $length
     * @return string
     */
    public function getRandomString($length = 45)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    /**
     * @return array
     */
    public function getSensitiveData(string $invoice)
    {
        return [
            'merchantId' => config('nagad.merchant_id'),
            'datetime' => Carbon::now(config('nagad.timezone'))->format('YmdHis'),
            'orderId' => $invoice,
            'challenge' => $this->getRandomString(),
        ];
    }

    /**
     * @return string
     *
     * @throws InvalidPublicKey
     */
    public function encryptWithPublicKey(string $data)
    {
        $publicKey = "-----BEGIN PUBLIC KEY-----\n".config('nagad.public_key')."\n-----END PUBLIC KEY-----";
        $keyResource = openssl_get_publickey($publicKey);
        $status = openssl_public_encrypt($data, $cryptoText, $keyResource);
        if ($status) {
            return base64_encode($cryptoText);
        } else {
            throw new NagadInvalidPublicKey('Invalid Public key');
        }
    }

    /**
     * @return mixed
     */
    public static function decryptDataPrivateKey(string $data)
    {
        $private_key = "-----BEGIN RSA PRIVATE KEY-----\n".config('nagad.private_key')."\n-----END RSA PRIVATE KEY-----";
        openssl_private_decrypt(base64_decode($data), $plain_text, $private_key);

        return $plain_text;
    }

    /**
     * @return string
     *
     * @throws InvalidPrivateKey
     */
    public function signatureGenerate(string $data)
    {
        $private_key = "-----BEGIN RSA PRIVATE KEY-----\n".config('nagad.private_key')."\n-----END RSA PRIVATE KEY-----";
        $status = openssl_sign($data, $signature, $private_key, OPENSSL_ALGO_SHA256);
        if ($status) {
            return base64_encode($signature);
        } else {
            throw new NagadInvalidPrivateKey('Invalid private key');
        }

    }
}
