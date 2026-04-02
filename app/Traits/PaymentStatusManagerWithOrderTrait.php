<?php

declare(strict_types=1);

namespace Modules\Payment\Traits;

use Exception;
use Illuminate\Http\Request;
use Modules\Order\Enums\OrderStatus;
use Modules\Order\Models\Order;
use Modules\Order\Traits\OrderStatusManagerWithPaymentTrait;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Enums\PaymentStatusOld;
use Modules\Payment\Facades\Payment;
use Modules\Settings\Models\Settings;
use Throwable;

trait PaymentStatusManagerWithOrderTrait
{
    use OrderStatusManagerWithPaymentTrait;
    use PaymentTrait;

    /**
     * stripe
     *
     * @param  mixed  $order
     * @param  mixed  $request
     * @param  mixed  $settings
     */
    public function stripe($order, $request, $settings): void
    {
        try {
            $chosen_intent = '';
            // for single gateway options
            // if (isset($order->payment_intent)) {
            //     foreach ($order->payment_intent as $key => $intent) {
            //         if (strtoupper($settings->options['paymentGateway']) === $order->payment_gateway) {
            //             $chosen_intent = $intent;
            //         }
            //     }
            // }

            // for multi-gateway options
            if (isset($order->payment_intent)) {
                foreach ($order->payment_intent as $intent) {
                    if (mb_strtoupper($request['payment_gateway']) === $order->payment_gateway) {
                        $chosen_intent = $intent;
                    }
                }
            }

            $intent_secret = isset($chosen_intent->payment_intent_info) ? $chosen_intent->payment_intent_info['client_secret'] : null;
            $payment_intent_id = isset($chosen_intent->payment_intent_info) ? $chosen_intent->payment_intent_info['payment_id'] : null;

            if (isset($intent_secret) && isset($payment_intent_id)) {
                $retrieved_intent = Payment::retrievePaymentIntent($payment_intent_id);
                $retrieved_intent_status = $retrieved_intent->status;

                switch ($retrieved_intent_status) {
                    case 'succeeded':
                        $this->paymentSuccess($order);
                        break;

                    case 'requires_action':
                        $this->paymentProcessing($order);
                        break;

                    case 'requires_payment_method':
                        $this->paymentFailed($order);
                        break;
                }
            }
        } catch (Exception $e) {
            throw new Exception(SOMETHING_WENT_WRONG);
        }
    }

    /**
     * Status change for paypal
     *
     * @throws Exception
     */
    public function paypal(Order $order, Request $request, Settings $settings): void
    {
        try {
            $chosen_intent = '';

            // for multi-gateway options
            if (isset($order->payment_intent)) {
                foreach ($order->payment_intent as $intent) {
                    if (mb_strtoupper($request['payment_gateway']) === $order->payment_gateway) {
                        $chosen_intent = $intent;
                    }
                }
            }

            $paymentId = isset($chosen_intent->payment_intent_info) ? $chosen_intent->payment_intent_info['payment_id'] : null;
            if (isset($paymentId)) {
                $payment = Payment::verify($paymentId);
                if ($payment) {
                    $paymentStatus = $payment['status'];
                    switch (mb_strtolower($paymentStatus)) {
                        case PaymentStatusOld::COMPLETED->value:
                            $this->paymentSuccess($order);
                            break;
                        case 'payer_action_required':
                            $this->paymentProcessing($order);
                            break;
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception(SOMETHING_WENT_WRONG_WITH_PAYMENT);
        }
    }

    /**
     * Status change for razorpay
     *
     * @throws Exception
     */
    public function razorpay(Order $order, Request $request, Settings $settings): void
    {
        try {
            $chosen_intent = '';

            // for multi-gateway options
            if (isset($order->payment_intent)) {
                foreach ($order->payment_intent as $intent) {
                    if (mb_strtoupper($request['payment_gateway']) === $order->payment_gateway) {
                        $chosen_intent = $intent;
                    }
                }
            }

            $paymentId = isset($chosen_intent->payment_intent_info) ? $chosen_intent->payment_intent_info['payment_id'] : null;
            if (isset($paymentId)) {
                $paymentStatus = Payment::verify($paymentId);
                if ($paymentStatus) {
                    switch (mb_strtolower($paymentStatus)) {
                        case 'paid':
                            $this->paymentSuccess($order);
                            break;
                        case 'attempted':
                            $this->paymentProcessing($order);
                            break;
                        case PaymentStatusOld::FAILED->value:
                            $this->paymentFailed($order);
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception(SOMETHING_WENT_WRONG_WITH_PAYMENT);
        }
    }

    /**
     * Status change for mollie
     *
     * @throws Exception
     */
    public function mollie(Order $order, Request $request, Settings $settings): void
    {
        try {
            $chosen_intent = '';

            // for multi-gateway options
            if (isset($order->payment_intent)) {
                foreach ($order->payment_intent as $intent) {
                    if (ucfirst($request['payment_gateway']) === $intent->payment_gateway) {
                        $chosen_intent = $intent;
                    }
                }
            }

            $paymentId = isset($chosen_intent->payment_intent_info) ? $chosen_intent->payment_intent_info['payment_id'] : null;
            if (isset($paymentId)) {
                $paymentStatus = Payment::verify($paymentId);
                if ($paymentStatus) {
                    switch (mb_strtolower($paymentStatus)) {
                        case 'paid':
                            $this->paymentSuccess($order);
                            break;
                        case PaymentStatusOld::PENDING->value:
                            $this->paymentAwaitingForApproval($order);
                            break;
                        case PaymentStatusOld::FAILED->value:
                            $this->paymentFailed($order);
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception(SOMETHING_WENT_WRONG_WITH_PAYMENT);
        }
    }

    public function sslcommerz(Order $order, Request $request, Settings $settings): void
    {
        try {
            $chosen_intent = '';

            // for multi-gateway options
            if (isset($order->payment_intent)) {
                foreach ($order->payment_intent as $intent) {
                    if (mb_strtoupper($request['payment_gateway']) === $order->payment_gateway) {
                        $chosen_intent = $intent;
                    }
                }
            }

            $paymentId = isset($chosen_intent->payment_intent_info) ? $chosen_intent->payment_intent_info['payment_id'] : null;
            if (isset($paymentId)) {
                $paymentStatus = Payment::verify($paymentId);
                if ($paymentStatus) {
                    switch (mb_strtolower($paymentStatus)) {
                        case 'valid':
                            $this->paymentSuccess($order);
                            break;
                        case 'validated':
                            $this->paymentSuccess($order);
                            break;
                        case PaymentStatusOld::PENDING->value:
                            $this->paymentAwaitingForApproval($order);
                            break;
                        case PaymentStatusOld::FAILED->value:
                            $this->paymentFailed($order);
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception(SOMETHING_WENT_WRONG_WITH_PAYMENT);
        }
    }

    /**
     * Status change for paystack
     *
     * @throws Exception
     */
    public function paystack(Order $order, Request $request, Settings $settings): void
    {
        try {
            $chosen_intent = '';

            // for multi-gateway options
            if (isset($order->payment_intent)) {
                foreach ($order->payment_intent as $intent) {
                    if (mb_strtoupper($request['payment_gateway']) === $order->payment_gateway) {
                        $chosen_intent = $intent;
                    }
                }
            }

            $paymentId = isset($chosen_intent->payment_intent_info) ? $chosen_intent->payment_intent_info['payment_id'] : null;
            if (isset($paymentId)) {
                $paymentStatus = Payment::verify($paymentId);
                if ($paymentStatus) {
                    switch (mb_strtolower($paymentStatus)) {
                        case 'success':
                            $this->paymentSuccess($order);
                            break;
                        case PaymentStatusOld::FAILED->value:
                            $this->paymentFailed($order);
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception(SOMETHING_WENT_WRONG_WITH_PAYMENT);
        }
    }

    public function iyzico(Order $order, Request $request, Settings $settings): void
    {
        try {
            $chosen_intent = '';
            // for multi-gateway options
            if (isset($order->payment_intent)) {
                foreach ($order->payment_intent as $intent) {
                    if (mb_strtoupper($request['payment_gateway']) === $order->payment_gateway) {
                        $chosen_intent = $intent;
                    }
                }
            }

            $paymentId = isset($chosen_intent->payment_intent_info) ? $chosen_intent->payment_intent_info['payment_id'] : null;
            if (isset($paymentId)) {
                $paymentStatus = Payment::verify($paymentId);
                if ($paymentStatus) {
                    switch (mb_strtolower($paymentStatus)) {
                        case 'success':
                            $this->paymentSuccess($order);
                            break;
                        case PaymentStatusOld::FAILED->value:
                            $this->paymentFailed($order);
                            // no break
                        case 'init_threeds':
                            $this->paymentProcessing($order);
                            // no break
                        case 'callback_threeds':
                            $this->paymentProcessing($order);
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception(SOMETHING_WENT_WRONG_WITH_PAYMENT);
        }
    }

    /**
     * Status change for xendit
     *
     * @throws Exception
     */
    public function xendit(Order $order, Request $request, Settings $settings): void
    {
        try {
            $chosen_intent = '';
            // for multi-gateway options
            if (isset($order->payment_intent)) {
                foreach ($order->payment_intent as $intent) {
                    if (mb_strtoupper($request['payment_gateway']) === $order->payment_gateway) {
                        $chosen_intent = $intent;
                    }
                }
            }

            $paymentId = isset($chosen_intent->payment_intent_info) ? $chosen_intent->payment_intent_info['payment_id'] : null;
            if (isset($paymentId)) {
                $paymentStatus = Payment::verify($paymentId);
                if ($paymentStatus) {
                    switch (mb_strtolower($paymentStatus)) {
                        case 'paid':
                            $this->paymentSuccess($order);
                            break;
                        case PaymentStatusOld::FAILED->value:
                            $this->paymentFailed($order);
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception(SOMETHING_WENT_WRONG_WITH_PAYMENT);
        }
    }

    /**
     * Status change for bkash
     *
     * @throws Exception
     */
    public function bkash(Order $order, Request $request, Settings $settings): void
    {
        try {
            $chosen_intent = '';
            // for multi-gateway options
            if (isset($order->payment_intent)) {
                foreach ($order->payment_intent as $intent) {
                    if (mb_strtoupper($request['payment_gateway']) === $order->payment_gateway) {
                        $chosen_intent = $intent;
                    }
                }
            }

            $paymentId = isset($chosen_intent->payment_intent_info) ? $chosen_intent->payment_intent_info['payment_id'] : null;
            if (isset($paymentId)) {
                $paymentStatus = Payment::verify($paymentId);
                if ($paymentStatus) {
                    switch (mb_strtolower($paymentStatus)) {
                        case PaymentStatusOld::COMPLETED->value:
                            $this->paymentSuccess($order);
                            break;
                        case PaymentStatusOld::FAILED->value:
                            $this->paymentFailed($order);
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception(SOMETHING_WENT_WRONG_WITH_PAYMENT);
        }
    }

    /**
     * Status change for paymongo
     *
     * @throws Exception
     */
    public function paymongo(Order $order, Request $request, Settings $settings): void
    {
        try {
            $chosen_intent = '';
            // for multi-gateway options
            if (isset($order->payment_intent)) {
                foreach ($order->payment_intent as $intent) {
                    if (mb_strtoupper($request['payment_gateway']) === $order->payment_gateway) {
                        $chosen_intent = $intent;
                    }
                }
            }

            $paymentId = isset($chosen_intent->payment_intent_info) ? $chosen_intent->payment_intent_info['payment_id'] : null;
            if (isset($paymentId)) {
                $paymentStatus = Payment::verify($paymentId);
                if ($paymentStatus) {
                    switch (mb_strtolower($paymentStatus)) {
                        case 'paid':
                            $this->paymentSuccess($order);
                            break;
                        case 'chargeable':
                            $this->paymentAwaitingForApproval($order);
                            break;
                        case PaymentStatusOld::PENDING->value:
                            $this->paymentAwaitingForApproval($order);
                            break;
                        case PaymentStatusOld::FAILED->value:
                            $this->paymentFailed($order);
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception(SOMETHING_WENT_WRONG_WITH_PAYMENT.$e->getMessage());
        }
    }

    /**
     * Status change for flutterwave
     *
     * @throws Exception
     */
    public function flutterwave(Order $order, Request $request, Settings $settings): void
    {
        try {
            $chosen_intent = '';
            // for multi-gateway options
            if (isset($order->payment_intent)) {
                foreach ($order->payment_intent as $intent) {
                    if (mb_strtoupper($request['payment_gateway']) === $order->payment_gateway) {
                        $chosen_intent = $intent;
                    }
                }
            }

            $paymentId = isset($chosen_intent->payment_intent_info) ? $chosen_intent->payment_intent_info['payment_id'] : null;
            if (isset($paymentId)) {
                $paymentStatus = Payment::verify($paymentId);
                if ($paymentStatus) {
                    switch (mb_strtolower($paymentStatus)) {
                        case PaymentStatusOld::SUCCESSFUL->value:
                            $this->paymentSuccess($order);
                            break;
                        case PaymentStatusOld::FAILED->value:
                            $this->paymentFailed($order);
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception(SOMETHING_WENT_WRONG_WITH_PAYMENT);
        }
    }

    /**
     * paymentAwaitingForApproval
     *
     * @param  mixed  $order
     */
    public function paymentAwaitingForApproval($order): void
    {
        $order->order_status = OrderStatus::Pending->value;
        $order->payment_status = PaymentStatus::AwaitingForApproval->value;
        $order->save();
        $this->orderStatusManagementOnPayment($order, $order->order_status, $order->payment_status);
    }

    /**
     * Update DB status after payment success
     */
    protected function paymentSuccess($order): void
    {
        $order->order_status = OrderStatus::Processing->value;
        $order->payment_status = PaymentStatus::Success->value;
        $order->save();
        try {
            $children = json_decode($order->children);
        } catch (Throwable $th) {
            $children = $order->children;
        }
        if (is_array($children) && count($children)) {
            foreach ($order->children as $child_order) {
                $child_order->order_status = OrderStatus::Processing->value;
                $child_order->payment_status = PaymentStatus::Success->value;
                $child_order->save();
            }
        }
        $this->orderStatusManagementOnPayment($order, $order->order_status, $order->payment_status);
    }

    /**
     * Update DB status after payment processing
     */
    protected function paymentProcessing($order): void
    {
        $order->order_status = OrderStatus::Processing->value;
        $order->payment_status = PaymentStatus::Processing->value;
        $order->save();
        $this->orderStatusManagementOnPayment($order, $order->order_status, $order->payment_status);
    }

    /**
     * Update DB status after payment failed
     */
    protected function paymentFailed($order): void
    {
        $order->order_status = OrderStatus::Failed->value;
        $order->payment_status = PaymentStatus::Failed->value;
        $order->save();
        $this->orderStatusManagementOnPayment($order, $order->order_status, $order->payment_status);
    }
}
