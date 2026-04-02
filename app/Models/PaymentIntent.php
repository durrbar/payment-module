<?php

declare(strict_types=1);

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Ecommerce\Traits\TranslationTrait;
use Modules\Order\Models\Order;

#[Table('payment_intents')]
#[Unguarded]
#[Hidden([
    'created_at',
    'updated_at',
    'deleted_at',
])]
class PaymentIntent extends Model
{
    use HasUuids;
    use SoftDeletes;
    use TranslationTrait;

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    protected function casts(): array
    {
        return [
            'payment_intent_info' => 'json',
        ];
    }
}
