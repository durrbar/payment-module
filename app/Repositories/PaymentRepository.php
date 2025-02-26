<?php

namespace Modules\Payment\Repositories;

use Modules\Payment\Drivers\BasePaymentDriver;
use Modules\Payment\Models\Payment;

class PaymentRepository
{
    /**
     * Get payment details from the payment provider.
     */
    public function getPaymentDetails(BasePaymentDriver $provider, string $transactionId): Payment
    {
        return $provider->getPaymentDetails($transactionId);
    }

    /**
     * Update the payment status.
     */
    public function updatePaymentStatus(BasePaymentDriver $provider, string $transactionId, string $status, array $postData): void
    {
        $provider->updatePaymentStatus($transactionId, $status, $postData);
    }

    public function initiatePayment(BasePaymentDriver $provider, mixed $payment)
    {
        return $provider->initiatePayment($payment);
    }

    public function verifyPayment(BasePaymentDriver $provider, string $transactionId)
    {
        return $provider->verifyPayment($transactionId);
    }

    public function refundPayment(BasePaymentDriver $provider, mixed $payment)
    {
        return $provider->refundPayment($payment);
    }

    public function handleIPN(BasePaymentDriver $provider, array $data)
    {
        return $provider->handleIPN($data);
    }

    public function handleSuccess(BasePaymentDriver $provider, array $data): array
    {
        return $provider->handleSuccess($data);
    }

    public function handleFailure(BasePaymentDriver $provider, array $data): array
    {
        return $provider->handleFailure($data);
    }

    public function handleCancel(BasePaymentDriver $provider, array $data): array
    {
        return $provider->handleCancel($data);
    }
}
