<?php

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Ecommerce\Traits\TranslationTrait;
use Modules\User\Models\User;

class PaymentGateway extends Model
{
    use HasUuids;
    use SoftDeletes;
    use TranslationTrait;

    protected $table = 'payment_gateways';

    public $guarded = [];

    public function payment_methods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function users(): BelongsTo
    {
        return $this->BelongsTo(User::class, 'user_id');
    }
}
