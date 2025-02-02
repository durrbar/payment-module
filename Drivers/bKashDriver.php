<?php

namespace Modules\Payment\Drivers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Payment\Models\Payment;

class BKashDriver extends BasePaymentDriver
{
    protected $appKey;
    protected $appSecret;
    protected $username;
    protected $password;
    protected $token;
    protected $sandbox;

    public function __construct()
    {
        $this->appKey = config('payment.providers.bkash.app_key');
        $this->appSecret = config('payment.providers.bkash.app_secret');
        $this->username = config('payment.providers.bkash.username');
        $this->password = config('payment.providers.bkash.password');
        $this->token = $this->authenticate();
        $this->sandbox = config('payment.providers.bkash.sandbox', false);  // Default to false if not set

    }

    public function initiatePayment(mixed $payment): array
    {
        $response = $this->postRequest('checkout/payment/create', [
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'merchantInvoiceNumber' => $payment->currency,
            'intent' => 'sale',
            'callbackURL' => '$data',
        ]);

        if ($this->validateResponse($response)) {
            return $response;
        }

        Log::error('bKash payment initiation failed', ['response' => $response]);
        return [];
    }

    public function verifyPayment(string $transactionId): array
    {
        return $this->getRequest("checkout/payment/query/{$transactionId}");
    }

    public function refundPayment(mixed $payment): array
    {
        $response = $this->postRequest('checkout/payment/refund', [
            'transactionId' => $payment->transactionId,
            'amount' => $payment->amount,
        ]);

        if ($this->validateResponse($response)) {
            return $response;
        }

        Log::error('bKash payment refund failed', ['response' => $response]);
        return [];
    }

    public function handleIPN(array $data): array
    {
        return $this->processPaymentStatus($data['tran_id'], 'Pending', function ($order_details) use ($data) {
            // Verify the transaction before updating status
            if (true) {
                // Update the order status to 'Complete' if transaction is valid
                $this->updatePaymentStatus($order_details['tran_id'], 'Complete', []);

                return [
                    'status' => 'success',
                    'message' => 'Transaction successfully processed via IPN. Order status updated to Complete.'
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Transaction verification failed.'
            ];
        });
    }

    public function handleSuccess(array $data): array
    {
        return $this->processPaymentStatus($data['tran_id'], 'Pending', function ($order_details) {
            if (true) {
                $this->updatePaymentStatus($order_details['tran_id'], 'Processing', []);
                return [
                    'status' => 'success',
                    'message' => 'Transaction is successfully completed.'
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Transaction verification failed.'
            ];
        });
    }

    public function handleFailure(array $data): array
    {
        return $this->processPaymentStatus($data['tran_id'], 'Pending', function ($order_details) use ($data) {
            // Add logic for handling failure, such as logging or sending notifications
            $this->updatePaymentStatus($order_details['tran_id'], 'Failed', []);

            return [
                'status' => 'error',
                'message' => 'Transaction failed. Order status updated to Failed.'
            ];
        });
    }

    public function handleCancel(array $data): array
    {
        return $this->processPaymentStatus($data['tran_id'], 'Pending', function ($order_details) use ($data) {
            // Handle order cancellation, update status to 'Cancelled'
            $this->updatePaymentStatus($data['tran_id'], 'Cancelled', []);

            return [
                'status' => 'error',
                'message' => 'Transaction cancelled. Order status updated to Cancelled.'
            ];
        });
    }

    /**
     * Prepare the payment data specific to the provider.
     */
    private function preparePaymentData(Payment $payment): array
    {
        // Eager-load the required relationships inside the driver
        $payment->load(['order.customer', 'order.delivery', 'order.items']);

        // Fetch related data (customer, delivery, items) for Bkash payment
        $paymentData = [
            'total_amount' => $payment->amount,
            'currency' => $payment->currency ?? 'BDT',
            'tran_id' => $payment->transaction_id,
            'cus_name' => $payment->order->customer->name ?? 'Unknown Customer',
            'cus_email' => $payment->order->customer->email ?? 'no-email@example.com',
            'cus_phone' => $payment->order->customer->phone ?? 'Unknown Phone',
            'cus_add1' => $payment->order->customer->address ?? 'Unknown Address',
            'ship_name' => $payment->order->delivery->name ?? 'Default Shipping Name',
            'ship_add1' => $payment->order->delivery->address ?? 'Default Shipping Address',
            'product_name' => $payment->order->items->pluck('name')->join(', ') ?? 'Default Product',
            'product_category' => $payment->order->items->first()->category ?? 'Default Category',
            'product_profile' => 'physical-goods',
        ];

        // Add any provider-specific details if necessary

        return $paymentData;
    }

    /**
     * Generate the full URL for a given endpoint, considering the sandbox mode.
     *
     * @param string $type
     * @return string
     */
    protected function getEndpoint($type = 'payment'): string
    {
        $baseUrl = $this->sandbox
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
}
