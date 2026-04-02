<?php

declare(strict_types=1);

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Payment\Enums\PaymentStatusOld;
use Modules\Payment\Observers\PaymentObserver;

// use Modules\Payment\Database\Factories\PaymentFactory;

#[ObservedBy([PaymentObserver::class])]
class Payment extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * Boundary rule:
     * `status` column here uses PaymentStatusOld values, not PaymentStatus.
     */

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

    /**
     * Provider switching is only allowed while payment is in legacy pending state.
     */
    public function canChangeProvider(): bool
    {
        return (string) $this->status === PaymentStatusOld::PENDING->value;
    }
}
