<?php

namespace Modules\Payment\Payments;

use Exception;
use Luigel\Paymongo\Facades\Paymongo as PaymongoFacade;
use Modules\Core\Exceptions\DurrbarException;
use Modules\Order\Enums\OrderStatus;
use Modules\Order\Models\Order;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Traits\PaymentTrait;
use Stripe\Exception\SignatureVerificationException;
use Throwable;

class Paymongo extends Base implements PaymentInterface
{
    use PaymentTrait;

    public PaymongoFacade $PaymongoFacade;

    public function __construct()
    {
        parent::__construct();
        $this->PaymongoFacade = new PaymongoFacade(config('shop.paymongo.public_key'), config('shop.paymongo.secret_key'));
    }

    /**
     * Get payment intent for payment
     *
     * @throws DurrbarException
     */
    public function getIntent($data): array
    {
        try {
            extract($data);
            $redirectUrl = config('shop.shop_url');

            $order = PaymongoFacade::source()->create([
                'type' => $selected_payment_path,
                'amount' => round($amount),
                'currency' => $this->currency,
                'metadata' => [
                    'tracking_number' => $order_tracking_number,
                ],
                'redirect' => [
                    'success' => "{$redirectUrl}/orders/{$order_tracking_number}/thank-you",
                    'failed' => "{$redirectUrl}/orders/{$order_tracking_number}/thank-you",
                ],
            ]);

            return [
                'payment_id' => $order->id,
                'amount' => $order->amount,
                'invoice_id' => $order_tracking_number,
                'redirect_url' => $order->redirect['checkout_url'],
                'is_redirect' => true,
            ];
        } catch (Exception $e) {
            throw new DurrbarException(SOMETHING_WENT_WRONG_WITH_PAYMENT);
        }
    }

    /**
     * Verify a payment
     *
     * @param  $id
     * @return false|mixed
     *
     * @throws DurrbarException
     */
    public function verify($paymentId): mixed
    {
        $source = PaymongoFacade::source()->find($paymentId);
        try {
            if ($source->status == 'chargeable') {
                $order = PaymongoFacade::payment()->create([
                    'amount' => $source->amount,
                    'currency' => $this->currency,
                    'description' => 'Payment Source',
                    'statement_descriptor' => 'Source Paymongo',
                    'source' => [
                        'id' => $source->id,
                        'type' => 'source',
                    ],
                ]);

                return $order->status ?? false;
            }

            return $source->status ?? false;
        } catch (Exception $e) {
            throw new DurrbarException(SOMETHING_WENT_WRONG_WITH_PAYMENT);
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
        try {
            $payload = @file_get_contents('php://input');
            $signatureHeader = $_SERVER['HTTP_PAYMONGO_SIGNATURE'];
            $webhookSecretKey = config('shop.paymongo.webhook_sig');
        } catch (SignatureVerificationException $e) {
            // Invalid signature
            http_response_code(400);
            exit();
        }

        $eventStatus = $request['data']['attributes']['data']['attributes']['status'];
        switch ($eventStatus) {
            case 'chargeable':
                $this->updatePaymentOrderStatus($request, OrderStatus::PROCESSING, PaymentStatus::SUCCESS);
                break;
            case 'payment.paid':
                $this->updatePaymentOrderStatus($request, OrderStatus::PROCESSING, PaymentStatus::SUCCESS);
                break;
            case 'payment.failed ':
                $this->updatePaymentOrderStatus($request, OrderStatus::FAILED, PaymentStatus::FAILED);
                break;
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
        $trackingId = $request['data']['attributes']['data']['attributes']['metadata']['tracking_number'];
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
