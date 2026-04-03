<?php

declare(strict_types=1);

namespace Modules\Payment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Order\Models\Order;

class PaymentFailedEvent
{
    use Dispatchable;

    /**
     * Create a new event instance.
     */
    public function __construct(public Order $order) {}
}
