<?php

namespace Modules\Payment\Repositories;

use Modules\Payment\Drivers\BasePaymentDriver;

class PaymentRepository
{
    protected BasePaymentDriver $provider;

    public function __construct(BasePaymentDriver $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Get payment details from the payment provider.
     */
    public function getPaymentDetails(string $transactionId)
    {
        return $this->provider->getPaymentDetails($transactionId);
    }

    /**
     * Update the payment status.
     */
    public function updatePaymentStatus(string $transactionId, string $status, array $postData): void
    {
        $this->provider->updatePaymentStatus($transactionId, $status, $postData);
    }

    public function initiatePayment(mixed $payment)
    {
        return $this->provider->initiatePayment($payment);
    }

    public function verifyPayment(string $transactionId)
    {
        return $this->provider->verifyPayment($transactionId);
    }

    public function refundPayment(mixed $payment)
    {
        return $this->provider->refundPayment($payment);
    }

    public function handleIPN(array $data)
    {
        return $this->provider->handleIPN($data);
    }

    public function handleSuccess(array $data): array
    {
        return $this->provider->handleSuccess($data);
    }

    public function handleFailure(array $data): array
    {
        return $this->provider->handleFailure($data);
    }

    public function handleCancel(array $data): array
    {
        return $this->provider->handleCancel($data);
    }
}
