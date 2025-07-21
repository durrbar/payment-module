<?php

namespace Modules\Payment\Drivers\Nagad;

use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\Payment;

trait Helpers
{
    protected function validateConfig(array $config): void
    {
        $required = ['merchant_id', 'merchant_number', 'public_key', 'private_key'];
        foreach ($required as $key) {
            if (empty($config[$key])) {
                throw new \InvalidArgumentException("Missing Nagad config: {$key}");
            }
        }
    }

    protected function currentDateTime(): string
    {
        return now()->timezone('Asia/Dhaka')->format('YmdHis');
    }

    protected function encryptSensitiveData(Payment $payment): string
    {
        $data = json_encode([
            'merchantId' => $this->merchantId,
            'datetime' => $this->currentDateTime(),
            'orderId' => $payment->tran_id,
            'amount' => number_format($payment->amount, 2, '.', ''),
            'callbackUrl' => $this->callbackUrl,
        ]);

        return $this->rsaEncrypt($data, $this->publicKey);
    }

    protected function generateSignature(Payment $payment): string
    {
        $data = $payment->tran_id.$this->currentDateTime().$payment->amount;

        return $this->hmacHash($data, $this->privateKey);
    }

    protected function rsaEncrypt(string $data, string $publicKey): string
    {
        openssl_public_encrypt($data, $encrypted, $publicKey);

        return base64_encode($encrypted);
    }

    protected function hmacHash(string $data, string $key): string
    {
        return base64_encode(hash_hmac('sha256', $data, $key, true));
    }

    protected function validatePaymentObject(mixed $payment): void
    {
        if (! $payment instanceof Payment) {
            throw new \InvalidArgumentException('Invalid payment object type');
        }

        if ($payment->currency !== 'BDT') {
            throw new \InvalidArgumentException('Nagad only supports BDT currency');
        }
    }

    protected function handleInitiationResponse(array $response): array
    {
        if ($response['status'] !== 'Success') {
            throw new \Exception(
                $response['reason'] ?? 'Payment initialization failed'
            );
        }

        return [
            'status' => PaymentStatus::PENDING,
            'payment_id' => $response['paymentReferenceId'],
            'redirect_url' => $response['callBackUrl'],
            'qr_code' => $response['qrCode'] ?? null,
        ];
    }

    protected function handleVerificationResponse(array $response): array
    {
        if ($response['status'] !== 'Success') {
            throw new \Exception(
                $response['reason'] ?? 'Payment verification failed'
            );
        }

        return [
            'status' => PaymentStatus::COMPLETED,
            'transaction_id' => $response['paymentReferenceId'],
            'amount' => $response['amount'],
            'currency' => 'BDT',
        ];
    }

    protected function validateIPNData(array $data): void
    {
        $required = ['payment_ref_id', 'status', 'amount'];
        foreach ($required as $key) {
            if (! isset($data[$key])) {
                throw new \Exception("Missing IPN field: {$key}");
            }
        }
    }
}
