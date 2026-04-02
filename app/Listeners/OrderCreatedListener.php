<?php

declare(strict_types=1);

namespace Modules\Payment\Listeners;

use Modules\Order\Events\OrderCreatedEvent;
use Modules\Payment\Enums\PaymentStatusOld;
use Modules\Payment\Services\PaymentService;

final class OrderCreatedListener
{
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function handle(OrderCreatedEvent $event)
    {
        $order = $event->order;

        // Create a payment record (mark as pending initially)
        $this->paymentService->createPayment($order, PaymentStatusOld::PENDING->value);
    }
}
