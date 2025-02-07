<?php

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Payment\Database\Factories\DiscountFactory;

class Discount extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): DiscountFactory
    // {
    //     // return DiscountFactory::new();
    // }

    /**
     * Check if the discount is valid.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        // Check if the discount is active
        if (!$this->is_active) {
            return false;
        }

        // Check if the discount has expired
        if ($this->expires_at && $this->expires_at < now()) {
            return false;
        }

        // Check if the discount has exceeded its usage limit
        if ($this->usage_limit && $this->times_used >= $this->usage_limit) {
            return false;
        }

        return true;
    }
}
