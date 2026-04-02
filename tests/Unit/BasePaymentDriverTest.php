<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Unit;

use Modules\Payment\Drivers\BasePaymentDriver;
use Modules\Payment\Enums\PaymentStatusOld;
use Modules\Payment\Models\Payment;
use RuntimeException;
use Tests\TestCase;

final class BasePaymentDriverTest extends TestCase
{
    public function test_process_payment_status_returns_error_for_empty_inputs(): void
    {
        $driver = $this->makeDriver();

        $response = $driver->processPaymentStatus('', '', static fn (): array => ['status' => 'ok']);

        self::assertSame('error', $response['status']);
        self::assertSame('Invalid payment ID or status.', $response['message']);
    }

    public function test_process_payment_status_executes_callback_when_status_matches(): void
    {
        $driver = $this->makeDriver();
        $payment = new Payment();
        $payment->status = PaymentStatusOld::PENDING->value;
        $driver->paymentDetails = $payment;

        $response = $driver->processPaymentStatus(
            'TXN-123',
            PaymentStatusOld::PENDING->value,
            static fn (Payment $paymentDetails): array => ['status' => 'ok', 'current' => $paymentDetails->status]
        );

        self::assertSame('ok', $response['status']);
        self::assertSame(PaymentStatusOld::PENDING->value, $response['current']);
    }

    public function test_process_payment_status_blocks_when_already_processing(): void
    {
        $driver = $this->makeDriver();
        $payment = new Payment();
        $payment->status = PaymentStatusOld::PROCESSING->value;
        $driver->paymentDetails = $payment;

        $response = $driver->processPaymentStatus(
            'TXN-123',
            PaymentStatusOld::PENDING->value,
            static fn (): array => ['status' => 'ok']
        );

        self::assertSame('error', $response['status']);
        self::assertSame('Transaction is already being processed.', $response['message']);
    }

    public function test_process_payment_status_blocks_when_already_completed(): void
    {
        $driver = $this->makeDriver();
        $payment = new Payment();
        $payment->status = PaymentStatusOld::COMPLETED->value;
        $driver->paymentDetails = $payment;

        $response = $driver->processPaymentStatus(
            'TXN-123',
            PaymentStatusOld::PENDING->value,
            static fn (): array => ['status' => 'ok']
        );

        self::assertSame('error', $response['status']);
        self::assertSame('Transaction is already successfully completed.', $response['message']);
    }

    private function makeDriver(): BasePaymentDriver
    {
        return new class extends BasePaymentDriver
        {
            public ?Payment $paymentDetails = null;

            public function getPaymentDetails(string $tranId): Payment
            {
                if (! $this->paymentDetails instanceof Payment) {
                    throw new RuntimeException('Payment details not set for test.');
                }

                return $this->paymentDetails;
            }

            public function initiatePayment(mixed $payment): array
            {
                return [];
            }

            public function verifyPayment(string $transactionId): array
            {
                return [];
            }

            public function refundPayment(mixed $payment): array
            {
                return [];
            }

            public function handleIPN(array $data): array
            {
                return [];
            }

            public function handleSuccess(array $data): array
            {
                return [];
            }

            public function handleFailure(array $data): array
            {
                return [];
            }

            public function handleCancel(array $data): array
            {
                return [];
            }
        };
    }
}
