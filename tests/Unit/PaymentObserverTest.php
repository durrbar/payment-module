<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Unit;

use Illuminate\Support\Facades\Event;
use Modules\Payment\Enums\PaymentStatusOld;
use Modules\Payment\Events\PaymentSuccessEvent;
use Modules\Payment\Models\Payment;
use Modules\Payment\Observers\PaymentObserver;
use Tests\TestCase;

final class PaymentObserverTest extends TestCase
{
    public function test_updated_dispatches_payment_success_event_for_successful_status(): void
    {
        Event::fake();

        $payment = new Payment();
        $payment->status = PaymentStatusOld::SUCCESSFUL->value;

        $observer = new PaymentObserver();
        $observer->updated($payment);

        Event::assertDispatched(PaymentSuccessEvent::class);
    }

    public function test_updated_does_not_dispatch_payment_success_event_for_non_successful_status(): void
    {
        Event::fake();

        $payment = new Payment();
        $payment->status = PaymentStatusOld::PENDING->value;

        $observer = new PaymentObserver();
        $observer->updated($payment);

        Event::assertNotDispatched(PaymentSuccessEvent::class);
    }
}
