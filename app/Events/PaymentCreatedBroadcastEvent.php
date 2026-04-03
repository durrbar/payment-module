<?php

declare(strict_types=1);

namespace Modules\Payment\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class PaymentCreatedBroadcastEvent implements ShouldBroadcast
{
    use InteractsWithSockets;
    use SerializesModels;

    public string $message = 'Payment successfully created!';

    public function __construct(
        private string $customerId,
        public string $orderId,
        public string $redirectUrl
    ) {}

    public function broadcastOn(): Channel|array
    {
        return new PrivateChannel('PaymentCreated.'.$this->customerId);
    }

    public function broadcastQueue(): string
    {
        return 'broadcasts';
    }
}
