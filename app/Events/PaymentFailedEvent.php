<?php

namespace Modules\Payment\Events;

use Illuminate\Queue\SerializesModels;
use Modules\Order\Models\Order;

class PaymentFailedEvent
{
    use SerializesModels;

    public Order $order;

    /**
     * Create a new event instance.
     *
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }
}
