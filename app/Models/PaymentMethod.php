<?php

declare(strict_types=1);

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Ecommerce\Traits\TranslationTrait;

#[Table('payment_methods')]
#[Unguarded]
class PaymentMethod extends Model
{
    use HasUuids;
    use SoftDeletes;
    use TranslationTrait;

    public function payment_gateways(): BelongsTo
    {
        return $this->BelongsTo(PaymentGateway::class, 'payment_gateway_id');
    }
}
