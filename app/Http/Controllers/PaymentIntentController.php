<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Modules\Core\Exceptions\DurrbarException;
use Modules\Core\Http\Controllers\CoreController;
use Modules\Payment\Repositories\PaymentIntentRepository;
use Modules\Settings\Models\Settings;

class PaymentIntentController extends CoreController
{
    public function __construct(
        private readonly PaymentIntentRepository $repository,
        private ?Settings $settings = null
    ) {
        $this->settings ??= Settings::first();
    }

    /**
     * getPaymentIntent
     *
     * This function create the payment intent for the payment & store that into database with related to that order.
     * So that, if the intent was kept track in any case for current or future payment.
     *
     */
    public function getPaymentIntent(Request $request): mixed
    {
        try {
            if (! auth()->check() && ! $this->settings->options['guestCheckout']) {
                throw new AuthorizationException();
            }

            return $this->repository->getPaymentIntent($request, $this->settings);
        } catch (DurrbarException $e) {
            throw new DurrbarException(SOMETHING_WENT_WRONG, $e->getMessage());
        }
    }
}
