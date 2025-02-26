<?php

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Payment\Observers\PaymentObserver;

// use Modules\Payment\Database\Factories\PaymentFactory;

#[ObservedBy([PaymentObserver::class])]
class Payment extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): PaymentFactory
    // {
    //     // return PaymentFactory::new();
    // }

    public function order(): BelongsTo
    {
        return $this->belongsTo(config('payment.order.model'), 'order_id', 'id');
    }

    public function canChangeProvider(): bool
    {
        return $this->status === 'pending';
    }
}
