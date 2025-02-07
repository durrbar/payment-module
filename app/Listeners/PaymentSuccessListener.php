<?php

namespace Modules\Payment\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Delivery\Services\DeliveryService;
use Modules\Invoice\Services\InvoiceService;
use Modules\Payment\Events\PaymentSuccessEvent;

class PaymentSuccessListener
{
    protected $deliveryService;
    protected $invoiceService;

    /**
     * Create the event listener.
     */
    public function __construct(DeliveryService $deliveryService, InvoiceService $invoiceService)
    {
        $this->deliveryService = $deliveryService;
        $this->invoiceService = $invoiceService;
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentSuccessEvent $event): void
    {
        $payment = $event->payment;
        $order = $payment->order; // Assumes the Payment model has a relationship with Order
        $customer = $order->customer;

        $this->invoiceService->updateInvoiceStatus($order->invoice, 'paid');

        if ($order) {
            // Schedule the delivery
            try {
                $this->deliveryService->scheduleDelivery($order);
                Log::info("Delivery scheduled for Order ID: {$order->id}");
            } catch (\Exception $e) {
                Log::error("Failed to schedule delivery for Order ID: {$order->id}. Error: " . $e->getMessage());
            }
        }
    }
}
