<?php

declare(strict_types=1);

namespace Modules\Payment\Events;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Order\Models\Order;

class PaymentSuccess implements ShouldQueue
{
    /**
     * Create a new event instance.
     */
    public function __construct(public readonly Order $order) {}
}
