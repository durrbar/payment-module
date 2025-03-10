<?php

namespace Modules\Payment\Services;

use Illuminate\Support\Facades\Log;
use Modules\Order\Models\Order;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Exceptions\InvalidProviderException;
use Modules\Payment\Models\Payment;
use Modules\Payment\Repositories\PaymentRepository;

class PaymentService
{
    protected PaymentRepository $paymentRepository;

    public function __construct(PaymentRepository $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * Create a payment record for an order.
     *
     * @param Order $order
     * @return Payment
     * @throws Exception
     */
    public function createPayment(Order $order): Payment
    {
        return $order->payment()->create([
            'status' => PaymentStatus::PENDING,
            'tran_id' => $this->generateTransactionId(),
            'amount' => $order->total_amount,
        ]);
    }

    /**
     * Initiates a payment transaction.
     *
     * @param string $transactionId The payment transaction ID.
     * @param string $provider name of payment provider
     * @return array{ status: string, gatewayRedirectURL: string } The response from the payment gateway.
     */
    public function initiatePayment(string $transactionId, ?string $provider = null): array
    {
        // Resolve the payment provider
        $provider = $this->resolveProvider($provider);
        // Fetch the payment details using the provider
        // $payment = $this->paymentRepository->getPaymentDetails($provider, $transactionId);
        // // Update if provider changed
        // if ($payment->provider !== $provider && $payment->canChangeProvider()) {
        //     $payment->update(['provider' => $provider]);
        // }
        $payment = [
            'amount' => '46',
            'tran_id' => 'hfjyfyjf'
        ];
        // Initiate payment via the repository
        return $this->paymentRepository->initiatePayment($provider, $payment);
    }

    /**
     * Verify payment.
     */
    public function verifyPayment(string $transactionId, ?string $provider = null): array
    {
        try {
            $providerInstance = $this->resolveProvider($provider);
            return $this->paymentRepository->verifyPayment($providerInstance, $transactionId);
        } catch (\Exception $e) {
            Log::error("Payment verification failed for transaction {$transactionId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Refund payment.
     */
    public function refundPayment(string $transactionId, float $amount, ?string $provider): array
    {
        // Resolve the payment provider
        $provider = $this->resolveProvider($provider);
        // Fetch the payment details using the provider
        $payment = $this->paymentRepository->getPaymentDetails($provider, $transactionId);
        // Refund payment
        return $this->paymentRepository->refundPayment($provider, $payment);
    }

    /**
     * Handle IPN (Instant Payment Notification) from the payment provider.
     */
    public function handleIPN(array $data, ?string $provider)
    {
        try {
            $providerInstance = $this->resolveProvider($provider);
            return $this->paymentRepository->handleIPN($providerInstance, $data);
        } catch (\Exception $e) {
            Log::error("IPN handling failed for $provider: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle success notification from the payment provider.
     */
    public function handleSuccess(array $data, ?string $provider)
    {
        try {
            $providerInstance = $this->resolveProvider($provider);
            return $this->paymentRepository->handleSuccess($providerInstance, $data);
        } catch (\Exception $e) {
            Log::error("Success handling failed for $provider: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle failure notification from the payment provider.
     */
    public function handleFailure(array $data, ?string $provider)
    {
        try {
            $providerInstance = $this->resolveProvider($provider);
            return $this->paymentRepository->handleFailure($providerInstance, $data);
        } catch (\Exception $e) {
            Log::error("Failure handling failed for $provider: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle cancel notification from the payment provider.
     */
    public function handleCancel(array $data, ?string $provider)
    {
        try {
            $providerInstance = $this->resolveProvider($provider);
            return $this->paymentRepository->handleCancel($providerInstance, $data);
        } catch (\Exception $e) {
            Log::error("Cancel handling failed for $provider: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate a unique transaction ID.
     *
     * @return string
     */
    private function generateTransactionId(): string
    {
        return 'TXN-' . now()->format('Ymd') . strtoupper(bin2hex(random_bytes(4)));
    }

    /**
     * Resolve the correct payment provider.
     */
    protected function resolveProvider(string $providerName = null)
    {
        if (!$providerName) {
            // Fetch the default provider if none is supplied
            $providerName = config('payment.defaults.provider');
        }

        // Fetch the provider configuration dynamically from the config file
        $driver = config('payment.providers.' . strtolower($providerName) . '.driver');
        if (!$driver) {
            throw new InvalidProviderException("Driver for provider '{$providerName}' is not defined in the configuration.");
        }
        // Return the resolved driver instance
        return app($driver);
    }
}
