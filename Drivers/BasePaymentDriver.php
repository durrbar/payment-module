<?php

namespace Modules\Payment\Drivers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Interfaces\PaymentDriverInterface;
use Modules\Payment\Models\Payment;

abstract class BasePaymentDriver implements PaymentDriverInterface
{
    /**
     * Fetch payment details by transaction ID.
     */
    public function getPaymentDetails(string $tranId): mixed
    {
        return Payment::where('tran_id', $tranId)
            ->select('tran_id', 'status', 'currency', 'amount')
            ->findOrFail();
    }

    /**
     * Update payment status in the database.
     */
    public function updatePaymentStatus(string $tranId, string $status, array $data): void
    {
        DB::transaction(function () use ($tranId, $status, $data) {
            Payment::updateOrInsert(
                ['tran_id' => $tranId],
                [
                    'amount' => $data['total_amount'],
                    'status' => $status,
                    'tran_id' => $tranId,
                    'currency' => $data['currency'],
                ]
            );
        });
    }

    /**
     * Process the payment status and execute callback logic.
     */
    public function processPaymentStatus(string $tranId, string $status, callable $next): array
    {
        // Validate and sanitize inputs
        if (empty($tranId) || empty($status)) {
            return [
                'status' => 'error',
                'message' => 'Invalid payment ID or status.'
            ];
        }

        // Fetch payment details based on transaction ID
        $paymentDetails = $this->getPaymentDetails($tranId);

        // If payment details not found, log and return error
        if (!$paymentDetails) {
            // Log error for debugging
            error_log("Payment not found for ID: $tranId");

            return [
                'status' => 'error',
                'message' => 'Payment not found.'
            ];
        }

        // Check if the payment status matches the expected status
        if ($paymentDetails->status === $status) {
            try {
                // Execute the callback logic if status matches
                return $next($paymentDetails);
            } catch (\Exception $e) {
                // Log callback exception
                error_log("Callback execution failed: " . $e->getMessage());

                return [
                    'status' => 'error',
                    'message' => 'An error occurred during callback execution.'
                ];
            }
        }

        // Handle cases for other statuses using constants
        switch ($paymentDetails->status) {
            case PaymentStatus::PROCESSING:
                return [
                    'status' => 'error',
                    'message' => 'Transaction is already being processed.'
                ];
            case PaymentStatus::COMPLETED:
                return [
                    'status' => 'error',
                    'message' => 'Transaction is already successfully completed.'
                ];
            default:
                // Log unexpected status
                error_log("Unexpected payment status: " . $paymentDetails->status);

                return [
                    'status' => 'error',
                    'message' => 'Invalid transaction status.'
                ];
        }
    }

    /**
     * Format the initial payment response.
     *
     * This method is responsible for formatting the response data after an initial
     * payment attempt. It logs an error if the payment status indicates a failure.
     *
     * @param string $status The status of the payment ('success' or 'error').
     * @param string|null $redirectURL The URL to redirect to after processing the payment, if applicable.
     * @param string $message A message detailing the outcome of the payment process.
     * @param array $response The original response data received from the payment provider.
     * @param string $provider The name of the payment provider.
     *
     * @return array{ status: string, redirectURL: string } An array containing the formatted payment response, including the status,
     *               redirect URL, message, and any additional data.
     */
    public function formatInitialPaymentResponse(string $status, string $redirectURL = null, string $message = 'An error occurred', array $response, string $provider): array
    {
        // Log an error if the status is 'error'
        if ($status === 'error') {
            Log::error($provider . ' create payment failed', ['response' => $response]);
        }

        // Return the formatted response
        return [
            'status' => $status,
            'redirectURL' => $redirectURL,
            'message' => $message,
            'additionalData' => [],
        ];
    }

}
