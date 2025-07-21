<?php

namespace Modules\Payment\Payments;

use Exception;
use Karim007\LaravelBkashTokenize\Facade\BkashPaymentTokenize;
use Modules\Core\Exceptions\DurrbarException;
use Modules\Order\Models\Order;
use Modules\Payment\Traits\PaymentTrait;

class Bkash extends Base implements PaymentInterface
{
    use PaymentTrait;

    public BkashPaymentTokenize $bkashClient;

    public function __construct()
    {
        parent::__construct();
        $this->bkashClient = new BkashPaymentTokenize(config('shop.bkash.app_Key'), config('shop.bkash.app_secret'), config('shop.bkash.username'), config('shop.bkash.password'), config('shop.bkash.callback_url'));
    }

    public function getIntent($data): array
    {
        try {
            extract($data);
            $inv = uniqid();
            $params = [
                'intent' => 'sale',
                'mode' => '0011',
                'payerReference' => $inv,
                'currency' => $this->currency,
                'amount' => round($amount),
                'merchantInvoiceNumber' => $inv,
                'callbackURL' => config('shop.shop_url')."/orders/{$order_tracking_number}/thank-you",
            ];

            $response = BkashPaymentTokenize::cPayment(json_encode($params));

            return [
                'order_tracking_number' => $order_tracking_number,
                'is_redirect' => true,
                'payment_id' => $response['paymentID'],
                'redirect_url' => $response['bkashURL'],
            ];
        } catch (Exception $e) {
            throw new DurrbarException(SOMETHING_WENT_WRONG_WITH_PAYMENT);
        }
    }

    public function verify($paymentId): mixed
    {

        try {
            $result = BkashPaymentTokenize::executePayment($paymentId);
            if (! $result) {
                $result = BkashPaymentTokenize::queryPayment($paymentId);
            }
            if ($result['statusCode'] == '2023' || $result['statusCode'] == '2056') {
                return 'failed';
            }

            return isset($result['transactionStatus']) ? $result['transactionStatus'] : false;
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
        // Verify webhook

    }

    /**
     * Update Payment and Order Status
     */
    public function updatePaymentOrderStatus($request, $orderStatus, $paymentStatus): void
    {
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
