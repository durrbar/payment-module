<?php

namespace Modules\Payment\Payments;

use Exception;
use Illuminate\Support\Str;
use Modules\Order\Enums\OrderStatus;
use Modules\Order\Models\Order;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\PaymentIntent;
use Modules\Payment\Traits\PaymentTrait;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Razorpay extends Base implements PaymentInterface
{
    use PaymentTrait;

    public Api $api;

    public function __construct()
    {
        parent::__construct();
        $this->api = new Api(config('shop.razorpay.key_id'), config('shop.razorpay.key_secret'));
    }

    /**
     * Get payment intent for payment
     *
     * @throws Exception
     */
    public function getIntent($data): array
    {
        try {
            extract($data);
            $order = $this->api->order->create([
                'receipt' => $order_tracking_number,
                'amount' => round($amount, 2) * 100,
                'currency' => $this->currency,
            ]);

            return [
                'payment_id' => $order->id,
                'order_tracking_number' => $order->receipt,
                'currency' => $order->currency,
                'amount' => $order->amount,
                'is_redirect' => false,
            ];
        } catch (Exception $e) {
            throw new HttpException(400, SOMETHING_WENT_WRONG_WITH_PAYMENT);
        }
    }

    /**
     * Verify a payment
     *
     * @return false|mixed
     *
     * @throws Exception
     */
    public function verify($id): mixed
    {
        try {
            $order = $this->api->order->fetch($id);

            return isset($order->status) ? $order->status : false;
        } catch (Exception $e) {
            throw new HttpException(400, SOMETHING_WENT_WRONG_WITH_PAYMENT);
        }
    }

    /**
     * handleWebHooks
     *
     * @param  mixed  $request
     *
     * @throws Throwable
     */
    public function handleWebHooks($request): void
    {
        $webhookSecret = config('shop.razorpay.webhook_secret');
        $webhookBody = @file_get_contents('php://input');
        $webhookSignature = $request->header('X-Razorpay-Signature');

        try {
            if ($webhookBody && $webhookSignature && $webhookSecret) {
                $this->api->utility->verifyWebhookSignature($webhookBody, $webhookSignature, $webhookSecret);
            } else {
                // Invalid request
                http_response_code(400);
                exit();
            }
        } catch (SignatureVerificationError $e) {
            // Invalid signature
            http_response_code(400);
            exit();
        }

        $eventStatus = (string) Str::of($request->event)->replace('payment.', '', $request->event);

        switch ($eventStatus) {
            case 'dispute.won':
            case 'dispute.created':
            case 'authorized':
                $this->updatePaymentOrderStatus($request, OrderStatus::PENDING, PaymentStatus::PROCESSING);
                break;
            case 'captured':
                $this->updatePaymentOrderStatus($request, OrderStatus::PROCESSING, PaymentStatus::SUCCESS);
                break;
            case 'failed':
                $this->updatePaymentOrderStatus($request, OrderStatus::PENDING, PaymentStatus::FAILED);
        }

        // To prevent loop for any case
        http_response_code(200);
        exit();
    }

    /**
     * Update Payment and Order Status
     */
    public function updatePaymentOrderStatus($request, $orderStatus, $paymentStatus): void
    {
        $payload = $request->payload['payment']['entity'];
        $paymentIntent = PaymentIntent::whereJsonContains('payment_intent_info', ['payment_id' => $payload['order_id']])->first();
        $trackingId = $paymentIntent->tracking_number;
        $order = Order::where('tracking_number', '=', $trackingId)->first();
        $this->webhookSuccessResponse($order, $orderStatus, $paymentStatus);
    }

    /**
     * createCustomer
     *
     * @param  mixed  $request
     */
    public function createCustomer($request): array
    {
        return [];
    }

    /**
     * attachPaymentMethodToCustomer
     */
    public function attachPaymentMethodToCustomer(string $retrieved_payment_method, object $request): object
    {
        return (object) [];
    }

    /**
     * detachPaymentMethodToCustomer
     */
    public function detachPaymentMethodToCustomer(string $retrieved_payment_method): object
    {
        return (object) [];
    }

    public function retrievePaymentIntent($payment_intent_id): object
    {
        return (object) [];
    }

    /**
     * confirmPaymentIntent
     */
    public function confirmPaymentIntent(string $payment_intent_id, array $data): object
    {
        return (object) [];
    }

    /**
     * setIntent
     */
    public function setIntent(array $data): array
    {
        return [];
    }

    /**
     * retrievePaymentMethod
     */
    public function retrievePaymentMethod(string $method_key): object
    {
        return (object) [];
    }
}
