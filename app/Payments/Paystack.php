<?php

namespace Modules\Payment\Payments;

use Exception;
use Illuminate\Support\Facades\Http;
use Modules\Order\Enums\OrderStatus;
use Modules\Order\Models\Order;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Traits\PaymentTrait;
use Razorpay\Api\Errors\SignatureVerificationError;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Unicodeveloper\Paystack\Facades\Paystack as PaystackFacade;

class Paystack extends Base implements PaymentInterface
{
    use PaymentTrait;

    public PaystackFacade $paystack;

    public function __construct()
    {
        parent::__construct();
        $this->paystack = new PaystackFacade(config('shop.paystack.secret_key'), config('shop.paystack.public_key'));
    }

    public function getIntent($data): array
    {
        try {
            extract($data);
            $paymentData = [
                'email' => $user_email ?? $order_tracking_number.'@order.com',
                'amount' => round($amount, 2) * 100,
                'currency' => $this->currency,
                'metadata' => [
                    'tracking_nunmber' => $order_tracking_number,
                ],
                'callback_url' => config('shop.shop_url')."/orders/{$order_tracking_number}/thank-you",
            ];

            $order = PaystackFacade::getAuthorizationResponse($paymentData);

            return [
                'order_tracking_number' => $order_tracking_number,
                'is_redirect' => true,
                'payment_id' => $order['data']['reference'],
                'redirect_url' => $order['data']['authorization_url'],
            ];
        } catch (Exception $e) {
            throw new HttpException(400, SOMETHING_WENT_WRONG_WITH_PAYMENT);
        }
    }

    public function verify($reference): mixed
    {
        try {
            $url = "https://api.paystack.co/transaction/verify/{$reference}";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.config('shop.paystack.secret_key'),
            ])->get($url);

            $result = json_decode($response, false);

            return isset($result->data->status) ? $result->data->status : false;
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
        // Verify webhook
        try {
            $input = @file_get_contents('php://input');
            if ($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] !== hash_hmac('sha512', $input, config('shop.paystack.secret_key'))) {
                exit();
            }
            $event = json_decode($input);
        } catch (SignatureVerificationError $e) {
            // Invalid signature
            http_response_code(400);
            exit();
        }

        switch ($event->data->status) {
            case 'success':
                $this->updatePaymentOrderStatus($request, OrderStatus::PROCESSING, PaymentStatus::SUCCESS);
                break;
            case 'pending':
                $this->updatePaymentOrderStatus($request, OrderStatus::PENDING, PaymentStatus::PENDING);
                break;
            case 'failed':
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
        $trackingId = $request->data['metadata']['tracking_nunmber'];
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
