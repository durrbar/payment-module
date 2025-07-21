<?php

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Ecommerce\Traits\TranslationTrait;

class PaymentMethod extends Model
{
    use SoftDeletes;
    use TranslationTrait;

    protected $table = 'payment_methods';

    public $guarded = [];

    public function payment_gateways(): BelongsTo
    {
        return $this->BelongsTo(PaymentGateway::class, 'payment_gateway_id');
    }
}
