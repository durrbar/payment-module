<?php

namespace Modules\Payment\Listeners;

use Modules\Payment\Events\PaymentCreatedEvent;
use Modules\Payment\Services\PaymentService;

class PaymentCreatedListener
{
    protected PaymentService $paymentService;

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
        // Delegate payment initiation to the PaymentService
        $this->paymentService->initiatePayment($event->payment->tran_id, $event->payment->provider);
    }
}
