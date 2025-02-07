<?php

namespace Modules\Payment\Observers;

use Modules\Payment\Events\PaymentCreatedEvent;
use Modules\Payment\Events\PaymentSuccessEvent;
use Modules\Payment\Models\Payment;

class PaymentObserver
{
    public function __construct()
    {
        //
    }

    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        // Check if the related order was created via a web request
        if (app()->bound('web_created_order_' . $payment->order_id)) {
            event(new PaymentCreatedEvent($payment));
        }
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        // Check if the payment status has changed to 'successful'
        if ($payment->isDirty('status') && $payment->status === 'successful') {
            event(new PaymentSuccessEvent($payment));
        }
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        //
    }

    /**
     * Handle the Payment "restored" event.
     */
    public function restored(Payment $payment): void
    {
        //
    }

    /**
     * Handle the Payment "force deleted" event.
     */
    public function forceDeleted(Payment $payment): void
    {
        //
    }
}
