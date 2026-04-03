<?php

declare(strict_types=1);

namespace Modules\Payment\Events;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Payment\Models\PaymentMethod;

class PaymentMethods implements ShouldQueue
{
    /**
     * Create a new event instance.
     */
    public function __construct(public PaymentMethod $payment_methods) {}
}
