<?php

declare(strict_types=1);

namespace Modules\Payment\Listeners;

use Modules\Payment\Events\PaymentCreatedBroadcastEvent;
use Modules\Payment\Events\PaymentCreatedEvent;
use Modules\Payment\Services\PaymentService;

class PaymentCreatedListener
{
    private PaymentService $paymentService;

    /**
     * Create the event listener.
     */
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentCreatedEvent $event): void
    {
        // Delegate payment initiation
        $this->paymentService->initiatePayment($event->payment->tran_id, $event->payment->provider);

        // Dispatch a broadcast event
        event(new PaymentCreatedBroadcastEvent(
            $event->payment->order->customer->id,
            $event->payment->order->id,
            env('FRONTEND_URL').'/payment/'.$event->payment->tran_id
        ));
    }
}
