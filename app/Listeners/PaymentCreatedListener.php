<?php

declare(strict_types=1);

namespace Modules\Payment\Listeners;

use Modules\Payment\Events\PaymentCreatedBroadcastEvent;
use Modules\Payment\Events\PaymentCreatedEvent;
use Modules\Payment\Services\PaymentService;

class PaymentCreatedListener
{
    public function __construct(private readonly PaymentService $paymentService) {}

    public function handle(PaymentCreatedEvent $event): void
    {
        $this->paymentService->initiatePayment($event->payment->tran_id, $event->payment->provider);

        event(new PaymentCreatedBroadcastEvent(
            $event->payment->order->customer->id,
            $event->payment->order->id,
            env('FRONTEND_URL').'/payment/'.$event->payment->tran_id
        ));
    }
}
