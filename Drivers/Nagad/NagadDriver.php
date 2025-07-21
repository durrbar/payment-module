<?php

namespace Modules\Payment\Drivers\Nagad;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Modules\Payment\Drivers\BasePaymentDriver;
use Modules\Payment\Drivers\Nagad\Exceptions\NagadException;

class NagadDriver extends BasePaymentDriver
{
    use NagadHelpers;

    private string $merchantId;

    private string $merchantNumber;

    private string $baseUrl;

    private string $callbackUrl;

    private string $privateKey;

    private string $publicKey;

    private bool $sandbox_mode;

    public function __construct()
    {
        $this->merchantId = config('payment.provider.nagad.merchant_id');
        $this->merchantNumber = config('payment.provider.nagad.merchant_number');
        $this->baseUrl = config('payment.provider.nagad.base_url');
        $this->callbackUrl = config('payment.provider.nagad.callback_url');
        $this->privateKey = config('payment.provider.nagad.private_key');
        $this->publicKey = config('payment.provider.nagad.public_key');
        $this->sandbox_mode = config('payment.providers.nagad.sandbox', true);

        $this->initializeBaseUrl();
    }

    public function initiatePayment(mixed $payment): array
    {
        $this->validatePayment($payment);

        // Initiate the payment
        $paymentResponse = $this->createPayment($payment['amount'], $payment['tran_id']);

        // Return the payment options or an empty array
        return is_array($paymentResponse) ? $paymentResponse : [];
    }

    public function verifyPayment(string $transactionId): array
    {
        if (! $transactionId) {
            return $this->errorResponse('Transaction ID not provided');
        }

        // Send the request to verify the payment
        $response = $this->verifyTransaction($transactionId);

        if ($response['status'] !== 'Success') {
            throw new NagadException('Payment verification failed');
        }

        return [
            'status' => 'success',
            'tran_id' => $response['tran_id'],
            'amount' => $response['amount'],
            'currency' => $response['currency'],
        ];
    }

    public function refundPayment(mixed $payment): array
    {
        $response = $this->refund($payment->bank_tran_id, $payment->amount);

        if ($response['status'] !== 'SUCCESS') {
            throw new \Exception('Refund failed');
        }

        return [
            'status' => 'success',
            'tran_id' => $response['tran_id'],
            'refund_amount' => $response['refund_amount'],
        ];
    }

    public function handleIPN(array $data): array
    {
        return $this->processPaymentStatus($data['tran_id'], 'Pending', function ($details) {
            // Verify the transaction before updating status
            if ($this->verifyTransaction($details['tran_id'])) {
                // Update the order status to 'Complete' if transaction is valid
                $this->updatePaymentStatus($details['tran_id'], 'Complete', []);

                return [
                    'status' => 'success',
                    'message' => 'Transaction successfully processed via IPN. Order status updated to Complete.',
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Transaction verification failed.',
            ];
        });
    }

    public function handleSuccess(array $data): array
    {
        return $this->processPaymentStatus($data['tran_id'], 'Pending', function ($order_details) {
            if ($this->verifyTransaction($order_details['tran_id'])) {
                $this->updatePaymentStatus($order_details['tran_id'], 'Processing', []);

                return [
                    'status' => 'success',
                    'message' => 'Transaction is successfully completed.',
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Transaction verification failed.',
            ];
        });
    }

    public function handleFailure(array $data): array
    {
        return $this->processPaymentStatus($data['tran_id'], 'Pending', function ($order_details) {
            // Add logic for handling failure, such as logging or sending notifications
            $this->updatePaymentStatus($order_details['tran_id'], 'Failed', []);

            return [
                'status' => 'error',
                'message' => 'Transaction failed. Order status updated to Failed.',
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
                'message' => 'Transaction cancelled. Order status updated to Cancelled.',
            ];
        });
    }

    private function initializeBaseUrl()
    {
        $this->baseUrl = $this->sandbox_mode
            ? 'http://sandbox.mynagad.com:10080/remote-payment-gateway-1.0/api/dfs/'
            : 'https://api.mynagad.com/api/dfs/';
    }

    private function createPayment($amount, $invoice)
    {
        $initialize = $this->initializePayment($invoice);

        if ($initialize->sensitiveData && $initialize->signature) {
            $decryptData = json_decode($this->decryptDataPrivateKey($initialize->sensitiveData));
            $url = $this->baseUrl.'/check-out/complete/'.$decryptData->paymentReferenceId;
            $sensitiveOrderData = [
                'merchantId' => $this->merchantId,
                'orderId' => $invoice,
                'currencyCode' => '050',
                'amount' => $amount,
                'challenge' => $decryptData->challenge,
            ];

            return $this->sendPaymentRequest($url, $sensitiveOrderData);
        }
    }

    private function initializePayment($invoice)
    {
        $baseUrl = $this->baseUrl."check-out/initialize/{$this->merchantId}/{$invoice}";
        $sensitiveData = $this->getSensitiveData($invoice);
        $body = [
            'accountNumber' => $this->merchantNumber,
            'dateTime' => Carbon::now()->timezone(config('timezone'))->format('YmdHis'),
            'sensitiveData' => $this->encryptWithPublicKey(json_encode($sensitiveData)),
            'signature' => $this->signatureGenerate(json_encode($sensitiveData)),
        ];

        $response = $this->sendRequest($baseUrl, $body);
        $response = json_decode($response->body());
        if (isset($response->reason)) {
            throw new NagadException($response->message);
        }

        return $response;
    }

    private function sendPaymentRequest(string $url, array $sensitiveOrderData)
    {
        $response = $this->sendRequest($url, [
            'sensitiveData' => $this->encryptWithPublicKey(json_encode($sensitiveOrderData)),
            'signature' => $this->signatureGenerate(json_encode($sensitiveOrderData)),
            'merchantCallbackURL' => $this->callbackUrl,
        ]);

        return json_decode($response->body());
    }

    private function refund(string $paymentRefId, float $refundAmount, string $referenceNo = '', string $message = 'Requested for refund')
    {
        $paymentDetails = $this->verifyTransaction($paymentRefId);

        if (isset($paymentDetails->reason)) {
            throw new NagadException($paymentDetails->message);
        }

        if (empty($referenceNo)) {
            $referenceNo = $this->getRandomString(10);
        }

        $sensitiveOrderData = [
            'merchantId' => $this->merchantId,
            'originalRequestDate' => date('Ymd'),
            'originalAmount' => $paymentDetails->amount,
            'cancelAmount' => $refundAmount,
            'referenceNo' => $referenceNo,
            'referenceMessage' => $message,
        ];

        $response = $this->sendRequest($this->baseUrl."purchase/cancel?paymentRefId={$paymentDetails->paymentRefId}&orderId={$paymentDetails->orderId}", [
            'sensitiveDataCancelRequest' => $this->encryptWithPublicKey(json_encode($sensitiveOrderData)),
            'signature' => $this->signatureGenerate(json_encode($sensitiveOrderData)),
        ]);

        $responseData = json_decode($response->body());

        if (isset($responseData->reason)) {
            throw new NagadException($responseData->message);
        }

        return json_decode($this->decryptDataPrivateKey($responseData->sensitiveData));
    }

    private function verifyTransaction(string $paymentRefId)
    {
        $url = $this->baseUrl."verify/payment/{$paymentRefId}";
        $response = Http::withHeaders($this->headers())->get($url);

        return json_decode($response->body());
    }

    private function errorResponse(string $message): array
    {
        return [
            'error' => $message,
        ];
    }

    private function sendRequest(string $url, array $data)
    {
        return Http::withHeaders($this->headers())->post($url, $data);
    }

    protected function headers()
    {
        return [
            'Content-Type' => 'application/json',
            'X-KM-IP-V4' => $this->getIp(),
            'X-KM-Api-Version' => 'v-0.2.0',
            'X-KM-Client-Type' => 'PC_WEB',
        ];
    }

    private function validatePayment(array $payment): void
    {
        if (! isset($payment['tran_id'], $payment['amount'])) {
            throw new NagadException('Invalid payment data');
        }

        if ($payment['amount'] < 10) {
            throw new NagadException('Minimum payment amount is 10 BDT');
        }
    }
}
