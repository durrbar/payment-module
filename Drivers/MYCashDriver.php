<?php

declare(strict_types=1);

namespace Modules\Payment\Drivers;

use Modules\Payment\Drivers\Enums\DriverTransactionStatus;

class MYCashDriver extends BasePaymentDriver
{
    public function initiatePayment(mixed $payment): array
    {
        // Logic to initiate payment with bKash
        return ['status' => DriverTransactionStatus::Success->value, 'tran_id' => 'bkash_txn_67890'];
    }

    public function verifyPayment(string $transactionId): array
    {
        // Logic to verify payment with bKash
        return ['status' => DriverTransactionStatus::Verified->value, 'tran_id' => $transactionId];
    }

    public function refundPayment(mixed $payment): array
    {
        // Logic to refund payment with bKash
        return ['status' => DriverTransactionStatus::Refunded->value, 'tran_id' => $payment->transactionId];
    }

    public function handleIPN(array $data): array
    {
        return $this->processPaymentStatus($data['tran_id'], 'Pending', function ($order_details) {
            // Verify the transaction before updating status
            if (true) {
                // Update the order status to 'Complete' if transaction is valid
                $this->updatePaymentStatus($order_details['tran_id'], 'Complete', []);

                return [
                    'status' => DriverTransactionStatus::Success->value,
                    'message' => 'Transaction successfully processed via IPN. Order status updated to Complete.',
                ];
            }

            return [
                'status' => DriverTransactionStatus::Error->value,
                'message' => 'Transaction verification failed.',
            ];
        });
    }

    public function handleSuccess(array $data): array
    {
        return $this->processPaymentStatus($data['tran_id'], 'Pending', function ($order_details) {
            if (true) {
                $this->updatePaymentStatus($order_details['tran_id'], 'Processing', []);

                return [
                    'status' => DriverTransactionStatus::Success->value,
                    'message' => 'Transaction is successfully completed.',
                ];
            }

            return [
                'status' => DriverTransactionStatus::Error->value,
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
                'status' => DriverTransactionStatus::Error->value,
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
                'status' => DriverTransactionStatus::Error->value,
                'message' => 'Transaction cancelled. Order status updated to Cancelled.',
            ];
        });
    }
}
