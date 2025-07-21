<?php

namespace Modules\Payment\Payments;

use Exception;
use Illuminate\Http\Request;
use KingFlamez\Rave\Facades\Rave as FlutterwaveFacade;
use Modules\Core\Exceptions\DurrbarException;
use Modules\Order\Enums\OrderStatus;
use Modules\Order\Models\Order;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\PaymentIntent;
use Modules\Payment\Traits\PaymentTrait;

class Flutterwave extends Base implements PaymentInterface
{
    use PaymentTrait;

    public FlutterwaveFacade $flutterwave;

    public function __construct()
    {
        parent::__construct();
        $this->flutterwave = new FlutterwaveFacade(config('shop.flutterwave.secret_key'), config('shop.flutterwave.public_key'));
    }

    public function getIntent($data): array
    {
        try {
            extract($data);
            $reference = FlutterwaveFacade::generateReference();

            // Enter the details of the payment
            $paymentData = [
                'payment_options' => 'card,banktransfer',
                'amount' => number_format($amount, 2),
                'email' => $user_email ?? $order_tracking_number.'@email.com',
                'tx_ref' => $reference,
                'currency' => $this->currency,
                'redirect_url' => route('callback.flutterwave'),
                'meta' => [
                    'order_tracking_number' => $order_tracking_number,
                ],
                'customer' => [
                    'email' => $user_email ?? $order_tracking_number.'@email.com',
                ],
            ];

            $order = FlutterwaveFacade::initializePayment($paymentData);

            return [
                'order_tracking_number' => $order_tracking_number,
                'is_redirect' => true,
                'payment_id' => $paymentData['tx_ref'],
                'tx_ref_id' => $paymentData['tx_ref'],
                'redirect_url' => $order['data']['link'],
            ];
        } catch (Exception $e) {
            throw new DurrbarException(SOMETHING_WENT_WRONG_WITH_PAYMENT);
        }
    }

    /**
     *  Flutterwave callback
     *
     * @param  mixed  $request
     * @return void
     */
    public static function callback(Request $request)
    {
        try {
            $tx_ref = $request['tx_ref'];
            if ($request['status'] == 'cancelled') {
                $tracking_number1 = PaymentIntent::whereJsonContains('payment_intent_info->payment_id', $tx_ref)->first();

                return redirect(config('shop.shop_url')."/orders/{$tracking_number1->payment_intent_info['order_tracking_number']}/payment");
            }

            $transactionID = $request['transaction_id'];
            $result = FlutterwaveFacade::verifyTransaction($transactionID);
            $tracking_number = $result['data']['meta']['order_tracking_number'];
            PaymentIntent::whereJsonContains('payment_intent_info->payment_id', $tx_ref)->update([
                'payment_intent_info->payment_id' => $transactionID,
            ]);

            return redirect(config('shop.shop_url')."/orders/{$tracking_number}/thank-you");
        } catch (Exception $e) {
            throw new DurrbarException(SOMETHING_WENT_WRONG_WITH_PAYMENT);
        }
    }

    public function verify($transaction): mixed
    {

        try {
            $result = FlutterwaveFacade::verifyTransaction($transaction);

            return isset($result['data']['status']) ? $result['data']['status'] : false;
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
            $verified = FlutterwaveFacade::verifyWebhook();

            if ($verified && $request->event == 'charge.completed' && $request->data['status'] == 'successful') {
                $verificationData = FlutterwaveFacade::verifyTransaction($request->data['id']);
                if ($verificationData['status'] === 'success') {
                    $this->updatePaymentOrderStatus($request, OrderStatus::PROCESSING, PaymentStatus::SUCCESS);
                }
            }
        } catch (Exception $e) {
            throw new DurrbarException(SOMETHING_WENT_WRONG_WITH_PAYMENT);
        }
    }

    /**
     * Update Payment and Order Status
     */
    public function updatePaymentOrderStatus($request, $orderStatus, $paymentStatus): void
    {
        $paymentIntent = PaymentIntent::whereJsonContains('payment_intent_info', ['tx_ref_id' => $request['data']['tx_ref']])->first();
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
