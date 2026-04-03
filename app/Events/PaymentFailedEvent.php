<?php

declare(strict_types=1);

namespace Modules\Payment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Order\Models\Order;

class PaymentFailedEvent
{
    use Dispatchable;

    public function __construct(public readonly Order $order) {}
}
