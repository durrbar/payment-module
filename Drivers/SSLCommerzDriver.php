<?php

namespace Modules\Payment\Drivers;

use Illuminate\Support\Facades\Http;

class SSLCommerzDriver extends BasePaymentDriver
{
    private $store_id;
    private $store_password;
    private $sandbox_mode;

    public function __construct()
    {
        // Directly initialize configuration values in the constructor
        $this->store_id = config('payment.providers.sslcommerz.store_id');
        $this->store_password = config('payment.providers.sslcommerz.store_password');
        $this->sandbox_mode = config('payment.providers.sslcommerz.sandbox', true);
    }

    // Initiate payment request
    public function initiatePayment(array $data): array
    {
        // Prepare the payload with the appropriate details
        $payload = [
            'store_id' => $this->store_id,
            'store_password' => $this->store_password,
            'total_amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'BDT',
            'tran_id' => $data['transaction_id'],
            'cus_name' => $data['customer_name'],
            'cus_email' => $data['customer_email'],
            'cus_phone' => $data['customer_phone'],
            'success_url' => $data['success_url'],
            'fail_url' => $data['fail_url'],
            'cancel_url' => $data['cancel_url'],
        ];

        // Send the payment initiation request via the helper
        $response = $this->postRequest('payment', $payload);

        // Return the response as per the expected structure
        return [
            'status' => 'success',
            'redirect_url' => $response['redirect_url'],  // Assuming SSLCommerz returns a redirect URL
            'transaction_id' => $response['transaction_id'], // Assuming SSLCommerz returns the transaction ID
        ];
    }

    // Verify the payment status
    public function verifyPayment(string $transactionId): array
    {
        $payload = [
            'store_id' => $this->store_id,
            'store_password' => $this->store_password,
            'tran_id' => $transactionId,
        ];

        // Send the request to SSLCommerz to verify the payment
        $response = $this->postRequest('validate', $payload);

        if ($response['status'] !== 'VALID') {
            throw new \Exception('Payment verification failed');
        }

        return [
            'status' => 'success',
            'transaction_id' => $response['tran_id'],
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
            'tran_id' => $transactionId,
            'refund_amount' => $amount,
        ];

        // Send the request to SSLCommerz to process the refund
        $response = $this->postRequest('refund', $payload);

        if ($response['status'] !== 'SUCCESS') {
            throw new \Exception('Refund failed');
        }

        return [
            'status' => 'success',
            'transaction_id' => $response['tran_id'],
            'refund_amount' => $response['refund_amount'],
        ];
    }

    // Handle IPN (Instant Payment Notification) from SSLCommerz
    public function handleIPN(array $data): array
    {
        // Validate the IPN response
        if (!$this->validateResponse($data)) {
            throw new \Exception('Invalid IPN response');
        }

        return [
            'status' => $data['status'],
            'transaction_id' => $data['tran_id'],
            'amount' => $data['amount'],
        ];
    }

    // Get the appropriate endpoint for SSLCommerz based on mode
    private function getEndpoint($type = 'payment')
    {
        $baseUrl = $this->sandbox_mode
            ? 'https://sandbox.sslcommerz.com'
            : 'https://secure.sslcommerz.com';

        return $baseUrl . ($type === 'payment' ? '/gwprocess/v4/api.php' : '/validator/api/validationserverAPI.php');
    }

    // Helper method to make a POST request to the SSLCommerz API
    private function postRequest(string $type, array $data): array
    {
        $url = $this->getEndpoint($type);
        $response = Http::post($url, $data);

        if ($response->failed()) {
            throw new \Exception('Failed to communicate with SSLCommerz API: ' . $response->body());
        }

        return $response->json();
    }

    // Helper method to validate IPN response
    public static function validateResponse(array $data): bool
    {
        // Logic to validate IPN (you can add more complex validation based on SSLCommerz documentation)
        return isset($data['status']) && $data['status'] === 'VALID';
    }
}
