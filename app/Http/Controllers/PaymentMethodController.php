<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Modules\Core\Exceptions\DurrbarException;
use Modules\Core\Http\Controllers\CoreController;
use Modules\Payment\Facades\Payment;
use Modules\Payment\Http\Requests\PaymentMethodCreateRequest;
use Modules\Payment\Models\PaymentMethod;
use Modules\Payment\Repositories\PaymentMethodRepository;
use Modules\Payment\Traits\PaymentTrait;
use Modules\Settings\Models\Settings;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentMethodController extends CoreController
{
    use PaymentTrait;

    public function __construct(
        private readonly PaymentMethodRepository $repository,
        private ?Settings $settings = null
    ) {
        $this->settings ??= Settings::first();
    }

    /**
     * index
     *
     * Get all the available payment method (e.g. Card) of current customer.
     *
     */
    public function index(Request $request): mixed
    {
        // currently stripe has only card saving feature available. So, it's hardcoded here. In case of future it can be processed based on the selected payment gateway for saving cards
        $user = $request->user();

        return $this->repository->with('payment_gateways')->whereRelation('payment_gateways', 'user_id', $user->id)->whereRelation('payment_gateways', 'gateway_name', 'stripe')->get();
    }

    /**
     * store
     *
     * Create & store the payment method (e.g. Card) and store the only available & safe information in database.
     *
     */
    public function store(PaymentMethodCreateRequest $request): mixed
    {
        try {
            return $this->repository->storeCards($request, $this->settings);
        } catch (DurrbarException $e) {
            throw new DurrbarException(COULD_NOT_CREATE_THE_RESOURCE);
        }
    }

    /**
     * destroy
     *
     * Delete Payment method (e.g. Card) from a user.
     *
     *
     * @throws Exception
     */
    public function destroy(Request $request, string $id): mixed
    {
        return $this->deletePaymentMethod($id);
    }

    public function deletePaymentMethod(string $id): mixed
    {
        try {
            try {
                $retrieved_payment_method = PaymentMethod::where('id', $id)->first();
                Payment::detachPaymentMethodToCustomer($retrieved_payment_method->method_key);

                return $this->repository->findOrFail($id)->forceDelete();
            } catch (Exception $e) {
                throw new HttpException(409, COULD_NOT_DELETE_THE_RESOURCE);
            }
        } catch (DurrbarException $e) {
            throw new DurrbarException(COULD_NOT_DELETE_THE_RESOURCE, $e->getMessage());
        }
    }

    /**
     * getMethodKeyByCard
     *
     * When creating a payment method (e.g Card) during checkout, it needs to generate that payment method identifier.
     * It can be used, in case of payment methods where cards can be saved.
     *
     */
    public function savePaymentMethod(Request $request): mixed
    {
        switch ($request->payment_gateway) {
            case 'stripe':
                return $this->repository->saveStripeCard($request);
            default:
                return null;
        }
    }

    /**
     * saveCardIntent
     *
     * Save payment method (e.g. Card) for future usages.
     *
     */
    public function saveCardIntent(Request $request): mixed
    {
        $setupIntent = null;

        switch ($this->settings->options['paymentGateway']) {
            case 'stripe':
                $setupIntent = $this->repository->setStripeIntent($request);
        }

        return $setupIntent;
    }

    /**
     * setDefaultPaymentMethod
     *
     * This method initiate the functionalities to set a payment method (e.g. Card) as a default for any user.
     *
     */
    public function setDefaultCard(Request $request): mixed
    {
        try {
            return $this->repository->setDefaultPaymentMethod($request);
        } catch (DurrbarException $e) {
            throw new DurrbarException(COULD_NOT_CREATE_THE_RESOURCE);
        }
        // if system varies from payment-gateway to payment-gateway, then use this.
        // switch ($this->settings->options['paymentGateway']) {
        //     case 'stripe':
        //         $setDefaultPayment = $this->repository->setDefaultPaymentMethod($request);
        //         break;
        // }
    }
}
