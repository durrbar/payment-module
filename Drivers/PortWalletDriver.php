<?php

namespace Modules\Payment\Drivers;

use Illuminate\Support\Facades\Http;

class PortWalletDriver extends BasePaymentDriver
{
    private $store_id;
    private $store_password;
    private $sandbox_mode;

    public function __construct()
    {
        // Initialize configuration
        $this->store_id = config('payment.providers.portwallet.store_id');
        $this->store_password = config('payment.providers.portwallet.store_password');
        $this->sandbox_mode = config('payment.providers.portwallet.sandbox', true);
    }

    // Initiate payment request
    public function initiatePayment(array $data): array
    {
        $payload = [
            'store_id' => $this->store_id,
            'store_password' => $this->store_password,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'BDT',
            'transaction_id' => $data['transaction_id'],
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $data['customer_phone'],
            'success_url' => $data['success_url'],
            'fail_url' => $data['fail_url'],
            'cancel_url' => $data['cancel_url'],
        ];

        $response = $this->postRequest('payment', $payload);

        return [
            'status' => 'success',
            'redirect_url' => $response['redirect_url'],
            'transaction_id' => $response['transaction_id'],
        ];
    }

    // Verify the payment status
    public function verifyPayment(string $transactionId): array
    {
        $payload = [
            'store_id' => $this->store_id,
            'store_password' => $this->store_password,
            'transaction_id' => $transactionId,
        ];

        $response = $this->postRequest('validate', $payload);

        if ($response['status'] !== 'VALID') {
            throw new \Exception('Payment verification failed');
        }

        return [
            'status' => 'success',
            'transaction_id' => $response['transaction_id'],
            'amount' => $response['amount'],
            'currency' => $response['currency'],
        ];
    }

    // Refund the payment
    public function refundPayment(string $transactionId, float $amount): array
    {
        $payload = [
            'store_id' => $this->store_id,
            'store_password' => $this->store_password,
            'transaction_id' => $transactionId,
            'refund_amount' => $amount,
        ];

        $response = $this->postRequest('refund', $payload);

        if ($response['status'] !== 'SUCCESS') {
            throw new \Exception('Refund failed');
        }

        return [
            'status' => 'success',
            'transaction_id' => $response['transaction_id'],
            'refund_amount' => $response['refund_amount'],
        ];
    }

    // Handle IPN response
    public function handleIPN(array $data): array
    {
        if (!$this->validateResponse($data)) {
            throw new \Exception('Invalid IPN response');
        }

        return [
            'status' => $data['status'],
            'transaction_id' => $data['transaction_id'],
            'amount' => $data['amount'],
        ];
    }

    // Helper methods
    private function getEndpoint($type = 'payment')
    {
        $baseUrl = $this->sandbox_mode
            ? 'https://sandbox.portwallet.com'
            : 'https://secure.portwallet.com';

        return $baseUrl . ($type === 'payment' ? '/api/payment' : '/api/validate');
    }

    private function postRequest(string $type, array $data): array
    {
        $url = $this->getEndpoint($type);
        $response = Http::post($url, $data);

        if ($response->failed()) {
            throw new \Exception('Failed to communicate with PortWallet API');
        }

        return $response->json();
    }

    public static function validateResponse(array $data): bool
    {
        return isset($data['status']) && $data['status'] === 'VALID';
    }
}
