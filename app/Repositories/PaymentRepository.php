<?php

namespace Modules\Payment\Repositories;

use Modules\Payment\Services\Providers\BasePaymentProvider;

class PaymentRepository
{
    protected $provider;

    public function __construct(BasePaymentProvider $provider)
    {
        $this->provider = $provider;
    }

    public function initiatePayment(array $data)
    {
        return $this->provider->initiatePayment($data);
    }

    public function verifyPayment(string $transactionId)
    {
        return $this->provider->verifyPayment($transactionId);
    }

    public function refundPayment(string $transactionId, float $amount)
    {
        return $this->provider->refundPayment($transactionId, $amount);
    }

    public function handleIPN(array $data)
    {
        if (method_exists($this->provider, 'handleIPN')) {
            return $this->provider->handleIPN($data);
        }
    }
}
