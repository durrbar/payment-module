<?php

namespace Modules\Payment\Events;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Payment\Models\PaymentMethod;

class PaymentMethods implements ShouldQueue
{
    /**
     * @var PaymentMethod
     */
    public $payment_methods;

    /**
     * Create a new event instance.
     */
    public function __construct(PaymentMethod $payment_methods)
    {
        $this->payment_methods = $payment_methods;
    }
}
