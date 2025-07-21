<?php

namespace Modules\Payment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Payment\Models\Payment;

class PaymentSuccessEvent
{
    use Dispatchable;

    public Payment $payment;

    /**
     * Create a new event instance.
     *
     * @param  Payment  $order
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }
}
