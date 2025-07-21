<?php

namespace Modules\Payment\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class PaymentCreatedBroadcastEvent implements ShouldBroadcast
{
    use InteractsWithSockets;
    use SerializesModels;

    public string $message = 'Payment successfully created!';

    public string $orderId;

    public string $redirectUrl;

    private string $customerId;

    public function __construct(string $customerId, string $orderId, string $redirectUrl)
    {
        $this->customerId = $customerId;
        $this->orderId = $orderId;
        $this->redirectUrl = $redirectUrl;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('PaymentCreated.'.$this->customerId);
    }

    // Optional: Define broadcast queue name
    public function broadcastQueue()
    {
        return 'broadcasts';
    }
}
