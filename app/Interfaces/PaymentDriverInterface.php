<?php

namespace Modules\Payment\Interfaces;

interface PaymentDriverInterface
{
    /**
     * Initiates a payment transaction.
     *
     * @param  mixed  $payment  The payment data.
     * @return array The response from the payment gateway.
     */
    public function initiatePayment(mixed $payment): array;

    /**
     * Verifies a payment using the given transaction ID.
     *
     * @param  string  $transactionId  The transaction ID to verify.
     * @return array The response indicating the verification status.
     */
    public function verifyPayment(string $transactionId): array;

    /**
     * Refunds a payment transaction.
     *
     * @param  mixed  $payment  The payment data.
     * @return array The response from the refund process.
     */
    public function refundPayment(mixed $payment): array;

    /**
     * Handles an IPN (Instant Payment Notification).
     *
     * @param  array  $data  The IPN data sent by the payment gateway.
     * @return array The response indicating the result of the IPN handling.
     */
    public function handleIPN(array $data): array;

    /**
     * Handles a successful transaction.
     *
     * @param  array  $data  The transaction data received on success.
     * @return array The response indicating the result of the success handling.
     */
    public function handleSuccess(array $data): array;

    /**
     * Handles a failed transaction.
     *
     * @param  array  $data  The transaction data received on failure.
     * @return array The response indicating the result of the failure handling.
     */
    public function handleFailure(array $data): array;

    /**
     * Handles a canceled transaction.
     *
     * @param  array  $data  The transaction data received on cancellation.
     * @return array The response indicating the result of the cancellation handling.
     */
    public function handleCancel(array $data): array;
}
