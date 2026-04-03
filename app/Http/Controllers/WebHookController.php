<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\CoreController;
use Modules\Payment\Facades\Payment;
use Modules\Payment\Payments\Flutterwave;

class WebHookController extends CoreController
{
    public function stripe(Request $request): mixed
    {
        return $this->handleWebhook($request);
    }

    public function paypal(Request $request): mixed
    {
        return $this->handleWebhook($request);
    }

    public function razorpay(Request $request): mixed
    {
        return $this->handleWebhook($request);
    }

    public function mollie(Request $request): mixed
    {
        return $this->handleWebhook($request);
    }

    public function sslcommerz(Request $request): mixed
    {
        return $this->handleWebhook($request);
    }

    public function paystack(Request $request): mixed
    {
        return $this->handleWebhook($request);
    }

    public function paymongo(Request $request): mixed
    {
        return $this->handleWebhook($request);
    }

    public function xendit(Request $request): mixed
    {
        return $this->handleWebhook($request);
    }

    public function iyzico(Request $request): mixed
    {
        return $this->handleWebhook($request);
    }

    public function bkash(Request $request): mixed
    {
        return $this->handleWebhook($request);
    }

    public function flutterwave(Request $request): mixed
    {
        return $this->handleWebhook($request);
    }

    public function callback(Request $request): mixed
    {
        return Flutterwave::callback($request);
    }

    private function handleWebhook(Request $request): mixed
    {
        return Payment::handleWebHooks($request);
    }
}
