<?php

declare(strict_types=1);

namespace Modules\Payment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Payment\Models\Payment;

class PaymentSuccessEvent
{
    use Dispatchable;

    public function __construct(public readonly Payment $payment) {}
}
