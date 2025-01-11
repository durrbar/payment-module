<?php

namespace Modules\Payment\Drivers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BKashDriver extends BasePaymentDriver
{
    protected $appKey;
    protected $appSecret;
    protected $username;
    protected $password;
    protected $token;
    protected $sandbox_mode;

    public function __construct()
    {
        $this->appKey = config('payment.bkash.app_key');
        $this->appSecret = config('payment.bkash.app_secret');
        $this->username = config('payment.bkash.username');
        $this->password = config('payment.bkash.password');
        $this->token = $this->authenticate();
        $this->sandbox_mode = config('payment.bkash.sandbox_mode', false);  // Default to false if not set

    }

    /**
     * Generate the full URL for a given endpoint, considering the sandbox mode.
     *
     * @param string $type
     * @return string
     */
    protected function getEndpoint($type = 'payment'): string
    {
        $baseUrl = $this->sandbox_mode
            ? 'https://sandbox.bkash.com'
            : 'https://secure.bkash.com';

        return $baseUrl . ($type === 'payment' ? '/api/payment' : '/api/validate');
    }

    /**
     * Authenticate with bKash to obtain an access token.
     *
     * @return string|null
     */
    protected function authenticate()
    {
        $response = $this->postRequest('token/grant', [
            'app_key' => $this->appKey,
            'app_secret' => $this->appSecret,
        ]);

        return $this->validateResponse($response) ? $response['id_token'] : null;
    }

    /**
     * Send a POST request to the given endpoint.
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    protected function postRequest(string $endpoint, array $data): array
    {
        $response = Http::withToken($this->token)
            ->post($this->getEndpoint($endpoint), $data);

        return $response->successful() ? $response->json() : [];
    }

    /**
     * Send a GET request to the given endpoint.
     *
     * @param string $endpoint
     * @return array
     */
    protected function getRequest(string $endpoint): array
    {
        $response = Http::withToken($this->token)
            ->get($this->getEndpoint($endpoint));

        return $response->successful() ? $response->json() : [];
    }

    /**
     * Validate the response from bKash API.
     *
     * @param array $response
     * @return bool
     */
    protected function validateResponse(array $response): bool
    {
        // You can add further checks like response status codes or specific response data
        return isset($response['id_token']) && !empty($response['id_token']);
    }

    /**
     * Initiate a payment with bKash.
     *
     * @param array $data
     * @return array
     */
    public function initiatePayment(array $data): array
    {
        $response = $this->postRequest('checkout/payment/create', [
            'amount' => $data['amount'],
            'currency' => 'BDT',
            'merchantInvoiceNumber' => $data['invoice'],
            'intent' => 'sale',
            'callbackURL' => $data['callback_url'],
        ]);

        if ($this->validateResponse($response)) {
            return $response;
        }

        Log::error('bKash payment initiation failed', ['response' => $response]);
        return [];
    }

    /**
     * Verify a payment with bKash.
     *
     * @param string $transactionId
     * @return array
     */
    public function verifyPayment(string $transactionId): array
    {
        return $this->getRequest("checkout/payment/query/{$transactionId}");
    }

    /**
     * Refund a payment with bKash.
     *
     * @param string $transactionId
     * @param float $amount
     * @return array
     */
    public function refundPayment(string $transactionId, float $amount): array
    {
        $response = $this->postRequest('checkout/payment/refund', [
            'transactionId' => $transactionId,
            'amount' => $amount,
        ]);

        if ($this->validateResponse($response)) {
            return $response;
        }

        Log::error('bKash payment refund failed', ['response' => $response]);
        return [];
    }

    /**
     * Handle IPN (Instant Payment Notification) from bKash.
     *
     * @param array $data
     * @return array
     */
    public function handleIPN(array $data): array
    {
        if ($data['status'] === 'success') {
            return [
                'status' => 'success',
                'message' => 'Payment successful',
                'transaction_id' => $data['transaction_id'],
            ];
        }

        return [
            'status' => 'failed',
            'message' => 'Payment failed',
            'transaction_id' => $data['transaction_id'],
        ];
    }
}
