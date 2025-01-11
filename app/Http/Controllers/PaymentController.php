<?php

namespace Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Payment\Drivers\BasePaymentDriver;
use Modules\Payment\Repositories\PaymentRepository;
use Modules\Payment\Services\DiscountService;

class PaymentController extends Controller
{
    /**
     * Make payment through the selected provider.
     */
    public function makePayment(Request $request)
    {
        try {
            $provider = $this->resolveProvider($request->input('provider'));

            $repository = new PaymentRepository($provider);

            $response = $repository->initiatePayment($request->all());

            return response()->json($response, 200);
        } catch (Exception $e) {
            Log::error('Payment initiation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Payment initiation failed'], 400);
        }
    }

    /**
     * Verify the payment transaction with the selected provider.
     */
    public function verifyPayment(Request $request, $transactionId)
    {
        try {
            $provider = $this->resolveProvider($request->input('provider'));

            $repository = new PaymentRepository($provider);

            $response = $repository->verifyPayment($transactionId);

            return response()->json($response, 200);
        } catch (Exception $e) {
            Log::error('Payment verification failed: ' . $e->getMessage());
            return response()->json(['error' => 'Payment verification failed'], 400);
        }
    }

    /**
     * Refund a payment with the selected provider.
     */
    public function refundPayment(Request $request)
    {
        try {
            $provider = $this->resolveProvider($request->input('provider'));

            $repository = new PaymentRepository($provider);

            $response = $repository->refundPayment(
                $request->input('transaction_id'),
                $request->input('amount')
            );

            return response()->json($response, 200);
        } catch (Exception $e) {
            Log::error('Payment refund failed: ' . $e->getMessage());
            return response()->json(['error' => 'Payment refund failed'], 400);
        }
    }

    /**
     * Apply a discount to the amount.
     */
    public function applyDiscount(Request $request)
    {
        try {
            $discountService = app(DiscountService::class);

            $discountedAmount = $discountService->applyDiscount(
                $request->input('discount_code'),
                $request->input('amount')
            );

            return response()->json([
                'discounted_amount' => $discountedAmount
            ], 200);
        } catch (Exception $e) {
            Log::error('Discount application failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid discount code or amount'], 400);
        }
    }

    public function ipn(Request $request, string $provider)
    {
        try {
            // Resolve the provider dynamically based on the URL
            $providerInstance = $this->resolveProvider($provider);

            // Initialize the repository with the provider
            $repository = new PaymentRepository($providerInstance);

            // Handle the IPN notification
            return $repository->handleIPN($request->all());
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Resolve the correct payment provider.
     *
     * @param string $providerName
     * @return object
     */
    private function resolveProvider(string $providerName): BasePaymentDriver
    {
        $providerClasses = [
            'bkash' => \Modules\Payment\Drivers\BkashDriver::class,
            'dbblrocket' => \Modules\Payment\Drivers\DBBLRocketDriver::class,
            'islamicwallet' => \Modules\Payment\Drivers\IslamicWalletDriver::class,
            'mcash' => \Modules\Payment\Drivers\MCashDriver::class,
            'mycash' => \Modules\Payment\Drivers\MYCashDriver::class,
            'nagad' => \Modules\Payment\Drivers\NagadDriver::class,
            'portwallet' => \Modules\Payment\Drivers\PortWalletDriver::class,
            'sslcommerz' => \Modules\Payment\Drivers\SSLCommerzDriver::class,
            'upay' => \Modules\Payment\Drivers\UpayDriver::class,
            'surecash' => \Modules\Payment\Drivers\SureCashDriver::class,
            '2checkout' => \Modules\Payment\Drivers\TwoCheckoutDriver::class,
        ];

        // Check if the provider exists
        if (!isset($providerClasses[strtolower($providerName)])) {
            throw new \Exception('Invalid payment provider: ' . $providerName);
        }

        // Return the resolved provider instance
        return new $providerClasses[strtolower($providerName)]();
    }
}
