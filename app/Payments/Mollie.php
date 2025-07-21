<?php

namespace Modules\Payment\Payments;

use Exception;
use Modules\Order\Enums\OrderStatus;
use Modules\Order\Models\Order;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Traits\PaymentTrait;
use Mollie\Laravel\Facades\Mollie as MollieFacade;
use Razorpay\Api\Errors\SignatureVerificationError;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Mollie extends Base implements PaymentInterface
{
    use PaymentTrait;

    protected MollieFacade $mollieFacade;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->mollieFacade = new MollieFacade(config('shop.mollie.mollie_key'));
    }

    public function getIntent($data): array
    {
        try {

            extract($data);
            $order = MollieFacade::api()->payments->create([
                'amount' => [
                    'currency' => $this->currency,
                    'value' => number_format($amount, 2),
                ],
                'description' => 'Order From '.$order_tracking_number,
                'redirectUrl' => config('shop.shop_url')."/orders/{$order_tracking_number}/thank-you",
                'webhookUrl' => config('shop.mollie.webhook_url'),
                'metadata' => [
                    'order_id' => $order_tracking_number,
                ],
            ]);

            return [
                'payment_id' => $order->id,
                'amount' => $order->amount->value,
                'invoice_id' => $order_tracking_number,
                'redirect_url' => $order->getCheckoutUrl(),
                'is_redirect' => true,
            ];
        } catch (Exception $e) {
            throw new HttpException(400, SOMETHING_WENT_WRONG_WITH_PAYMENT);
        }
    }

    public function verify($paymentId): mixed
    {
        try {
            $order = MollieFacade::api()->payments()->get($paymentId);

            return isset($order->status) ? $order->status : false;
        } catch (Exception $e) {
            throw new HttpException(400, SOMETHING_WENT_WRONG_WITH_PAYMENT);
        }
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
     *
     * @param  string  $retrieved_payment_method
     * @param  object  $request
     */
    public function attachPaymentMethodToCustomer($retrieved_payment_method, $request): object
    {
        return (object) [];
    }

    /**
     * detachPaymentMethodToCustomer
     *
     * @param  string  $retrieved_payment_method
     */
    public function detachPaymentMethodToCustomer($retrieved_payment_method): object
    {
        return (object) [];
    }

    /**
     * retrievePaymentIntent
     */
    public function retrievePaymentIntent($data): object
    {
        return (object) [];
    }

    /**
     * confirmPaymentIntent
     *
     * @param  string  $payment_intent_id
     * @param  array  $data
     */
    public function confirmPaymentIntent($payment_intent_id, $data): object
    {
        return (object) [];
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
        try {
            $payment = MollieFacade::api()->payments()->get($request->id);

            if ($payment->isPaid() && ! $payment->hasRefunds() && ! $payment->hasChargebacks()) {
                $this->updatePaymentOrderStatus($request, OrderStatus::PROCESSING, PaymentStatus::SUCCESS);
            } elseif ($payment->isOpen()) {
                $this->updatePaymentOrderStatus($request, OrderStatus::PENDING, PaymentStatus::PENDING);
            } elseif ($payment->isPending()) {
                $this->updatePaymentOrderStatus($request, OrderStatus::PENDING, PaymentStatus::AWAITING_FOR_APPROVAL);
            } elseif ($payment->isCanceled()) {
                $this->updatePaymentOrderStatus($request, OrderStatus::PENDING, PaymentStatus::PENDING);
            } elseif ($payment->isFailed() || $payment->isExpired() || $payment->hasRefunds() || $payment->hasRefunds() || $payment->hasChargebacks()) {
                $this->updatePaymentOrderStatus($request, OrderStatus::FAILED, PaymentStatus::FAILED);
            }

            // To prevent loop for any case
            http_response_code(200);
            exit();
        } catch (SignatureVerificationError $e) {
            // Invalid signature
            http_response_code(400);
            exit();
        }
    }

    /**
     * setIntent
     *
     * @param  array  $data
     */
    public function setIntent($data): array
    {
        return [];
    }

    /**
     * retrievePaymentMethod
     *
     * @param  string  $method_key
     */
    public function retrievePaymentMethod($method_key): object
    {
        return (object) [];
    }

    /**
     * Update Payment and Order Status
     */
    public function updatePaymentOrderStatus($request, $orderStatus, $paymentStatus): void
    {
        $payment = MollieFacade::api()->payments()->get($request->id);
        $trackingId = $payment->metadata->order_id;
        $order = Order::where('tracking_number', '=', $trackingId)->first();
        $this->webhookSuccessResponse($order, $orderStatus, $paymentStatus);
    }
}
