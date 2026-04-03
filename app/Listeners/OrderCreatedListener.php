<?php

declare(strict_types=1);

namespace Modules\Payment\Listeners;

use Modules\Order\Events\OrderCreatedEvent;
use Modules\Payment\Enums\PaymentStatusOld;
use Modules\Payment\Services\PaymentService;

class OrderCreatedListener
{
    public function __construct(private readonly PaymentService $paymentService) {}

    public function handle(OrderCreatedEvent $event): void
    {
        $order = $event->order;

        // Create a payment record (mark as pending initially)
        $this->paymentService->createPayment($order, PaymentStatusOld::PENDING->value);
    }
}
