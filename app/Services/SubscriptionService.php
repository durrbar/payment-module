<?php

namespace Modules\Payment\Services;

use Carbon\Carbon;

class SubscriptionService
{
    protected $plans;

    public function __construct()
    {
        // Define subscription plans
        $this->plans = [
            'basic' => [
                'price' => 10.00,
                'duration' => 30, // Days
            ],
            'premium' => [
                'price' => 25.00,
                'duration' => 90, // Days
            ],
            'annual' => [
                'price' => 100.00,
                'duration' => 365, // Days
            ],
        ];
    }

    public function subscribe(string $plan, string $userId): array
    {
        if (! isset($this->plans[$plan])) {
            return [
                'status' => 'error',
                'message' => 'Invalid subscription plan',
            ];
        }

        $subscription = $this->plans[$plan];
        $expiryDate = Carbon::now()->addDays($subscription['duration']);

        // Save subscription details to database (pseudo-code)
        // Subscription::create([...]);

        return [
            'status' => 'success',
            'plan' => $plan,
            'user_id' => $userId,
            'price' => $subscription['price'],
            'expires_at' => $expiryDate,
        ];
    }

    public function renewSubscription(string $userId, string $plan): array
    {
        // Renew the user's subscription
        return $this->subscribe($plan, $userId);
    }
}
