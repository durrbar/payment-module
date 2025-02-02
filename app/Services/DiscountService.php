<?php

namespace Modules\Payment\Services;

use Modules\Payment\Models\Discount;

class DiscountService
{
    /**
     * Apply and calculate discount for a given code and amount.
     *
     * @param string $code
     * @param float $amount
     * @return array
     */
    public function applyDiscount(string $code, float $amount): array
    {
        // Try to fetch the discount from the database
        $discount = Discount::where('code', $code)->first();

        if (!$discount || !$discount->isValid()) {
            return [
                'status' => 'error',
                'message' => 'Invalid or expired discount code.',
                'original_amount' => $amount,
                'discounted_amount' => $amount,
                'discount_amount' => 0,
            ];
        }

        // Calculate the discount
        $discountAmount = $discount->type === 'percentage'
            ? ($amount * $discount->value / 100)
            : $discount->value;

        $discount->increment('times_used');

        $finalAmount = max(0, $amount - $discountAmount); // Ensure no negative amounts

        return [
            'status' => 'success',
            'message' => 'Discount applied successfully.',
            'original_amount' => $amount,
            'discount_code' => $code,
            'discount_type' => $discount->type,
            'discount_value' => $discount->value,
            'discount_amount' => $discountAmount,
            'discounted_amount' => $finalAmount,
        ];
    }
}
