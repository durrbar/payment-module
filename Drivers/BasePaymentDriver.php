<?php

namespace Modules\Payment\Drivers;

abstract class BasePaymentDriver
{
    abstract public function initiatePayment(array $data): array;
    abstract public function verifyPayment(string $transactionId): array;
    abstract public function refundPayment(string $transactionId, float $amount): array;
    abstract public function handleIPN(array $data): array;
}
