<?php

declare(strict_types=1);

namespace Modules\Payment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Order\Models\Order;

class PaymentFailedEvent
{
    use Dispatchable;

    public Order $order;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }
}
