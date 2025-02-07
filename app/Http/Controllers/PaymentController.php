<?php

namespace Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Modules\Payment\Http\Requests\PaymentRequest;
use Modules\Payment\Http\Requests\RefundRequest;
use Modules\Payment\Services\DiscountService;
use Modules\Payment\Services\PaymentService;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Make payment through the selected provider.
     */
    public function makePayment(PaymentRequest $request)
    {
        $validated = $request->validated();

        return $this->handlePaymentServiceMethod(function () use ($validated) {
            $response = $this->paymentService->initiatePayment(
                $validated['tran_id'],
                $validated['provider'] ?? null
            );
            return response()->json($response, Response::HTTP_OK);
        });
    }

    /**
     * Verify the payment transaction with the selected provider.
     */
    public function verifyPayment(Request $request, $transactionId)
    {
        return $this->handlePaymentServiceMethod(function () use ($transactionId, $request) {
            $response = $this->paymentService->verifyPayment($transactionId, $request->input('provider'));
            return response()->json($response, Response::HTTP_OK);
        });
    }

    /**
     * Refund a payment with the selected provider.
     */
    public function refundPayment(RefundRequest $request)
    {
        $validated = $request->validated();

        return $this->handlePaymentServiceMethod(function () use ($validated) {
            $response = $this->paymentService->refundPayment(
                $validated['tran_id'],
                $validated['amount'],
                $validated['provider']
            );
            return response()->json($response, Response::HTTP_OK);
        });
    }

    /**
     * Apply a discount to the amount.
     */
    public function applyDiscount(Request $request)
    {
        return $this->handlePaymentServiceMethod(function () use ($request) {
            $discountService = app(DiscountService::class);

            $discountedAmount = $discountService->applyDiscount(
                $request->input('discount_code'),
                $request->input('amount')
            );

            return response()->json([
                'discounted_amount' => $discountedAmount
            ], Response::HTTP_OK);
        });
    }

    /**
     * Handle IPN (Instant Payment Notification).
     */
    public function ipn(Request $request, string $provider)
    {
        return $this->handlePaymentServiceMethod(function () use ($request, $provider) {
            $response = $this->paymentService->handleIPN($request->all(), $provider);
            return response()->json($response);
        });
    }

    /**
     * Handle success payment notification.
     */
    public function success(Request $request, string $provider)
    {
        return $this->handlePaymentServiceMethod(function () use ($request, $provider) {
            $response = $this->paymentService->handleSuccess($request->all(), $provider);
            return response()->json($response);
        });
    }

    /**
     * Handle failed payment notification.
     */
    public function fail(Request $request, string $provider)
    {
        return $this->handlePaymentServiceMethod(function () use ($request, $provider) {
            $response = $this->paymentService->handleFailure($request->all(), $provider);
            return response()->json($response);
        });
    }

    /**
     * Handle canceled payment notification.
     */
    public function cancel(Request $request, string $provider)
    {
        return $this->handlePaymentServiceMethod(function () use ($request, $provider) {
            $response = $this->paymentService->handleCancel($request->all(), $provider);
            return response()->json($response);
        });
    }

    /**
     * Centralized error handling for payment service methods.
     */
    private function handlePaymentServiceMethod(callable $method)
    {
        try {
            return $method();
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
