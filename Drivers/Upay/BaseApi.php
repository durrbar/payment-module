<?php

declare(strict_types=1);

namespace Modules\Payment\Drivers\Upay;

// use Codeboxr\Upay\Exception\UpayException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class BaseApi
{
    /**
     * @var string
     */
    private $token = '';

    /**
     * @var string
     */
    private $baseUrl = '';

    public function __construct()
    {
        $this->baseUrl = config('payment.upay.sandbox') === true ? 'https://uat-pg.upay.systems/' : 'https://pg.upaysystem.com/';
    }

    /**
     * Set headers
     *
     * @return string[]
     *
     * @throws UpayException
     */
    private function headers()
    {
        return [
            'Authorization' => "UPAY {$this->getToken()}",
            'Accept' => 'application/json',
        ];
    }

    /**
     * Token generate
     *
     * @return mixed|null
     *
     * @throws UpayException
     */
    private function getToken()
    {
        if (empty($this->token)) {
            $response = $this->request()
                ->post($this->baseUrl.'payment/merchant-auth/', [
                    'merchant_id' => config('payment.upay.merchant_id'),
                    'merchant_key' => config('payment.upay.merchant_key'),
                ]);

            $result = json_decode($response->body());
            if ($response->failed()) {
                throw new UpayException($result->message, $response->status());
            }

            $this->token = optional($result->data)->token;
        }

        return $this->token;
    }

    /**
     * request
     *
     * @return PendingRequest
     */
    private function request()
    {
        $request = Http::acceptJson();
        if (config('upay.sandbox') !== true) {
            $request->withOptions([
                'curl' => [CURLOPT_INTERFACE => config('payment.upay.server_ip'), CURLOPT_IPRESOLVE => 1],
            ]);
        }

        return $request;
    }
}
