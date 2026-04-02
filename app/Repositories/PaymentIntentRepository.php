<?php

declare(strict_types=1);

namespace Modules\Payment\Repositories;

use Modules\Core\Repositories\BaseRepository;
use Modules\Payment\Models\PaymentIntent;
use Modules\Payment\Traits\PaymentTrait;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;

class PaymentIntentRepository extends BaseRepository
{
    use PaymentTrait;

    /**
     * @var string[]
     */
    protected $dataArray = [
        'tracking_number',
    ];

    /**
     * boot
     */
    public function boot(): void
    {
        try {
            $this->pushCriteria(app(RequestCriteria::class));
        } catch (RepositoryException $e) {
            //
        }
    }

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return PaymentIntent::class;
    }

    /**
     * getPaymentIntent
     *
     * @return void
     */
    public function getPaymentIntent(mixed $request, mixed $settings): mixed
    {
        return $this->processPaymentIntent($request, $settings);
    }
}
