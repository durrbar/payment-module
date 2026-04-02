<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Unit;

use InvalidArgumentException;
use Mockery;
use Modules\Order\Models\Order;
use Modules\Payment\Enums\PaymentStatusOld;
use Modules\Payment\Models\Payment;
use Modules\Payment\Repositories\PaymentRepository;
use Modules\Payment\Services\PaymentService;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    protected PaymentService $paymentService;

    protected PaymentRepository $mockPaymentRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockPaymentRepository = Mockery::mock(PaymentRepository::class);

        $this->paymentService = new PaymentService(
            $this->mockPaymentRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_payment_successfully()
    {
        // Create a mock of the Order model
        /** @var Order|Mockery\MockInterface $order */
        $order = Mockery::mock(Order::class);
        $order->shouldAllowMockingMethod('payment'); // Allow mocking the 'payment' relationship

        $order->shouldReceive('getAttribute')
            ->with('total_amount')
            ->andReturn(500.00);

        // Mock the payment relationship and the create() method
        $paymentMock = Mockery::mock(Payment::class);
        $order->shouldReceive('payment->create')
            ->with(Mockery::on(function ($data) {
                return $data['status'] === PaymentStatusOld::PENDING->value
                    && str_starts_with($data['tran_id'], 'TXN-')
                    && $data['amount'] === 500.00;
            }))
            ->andReturn($paymentMock);

        // Call the method
        $result = $this->paymentService->createPayment($order);

        // Assertions
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertSame($paymentMock, $result);
    }

    public function test_create_payment_with_explicit_status_override(): void
    {
        /** @var Order|Mockery\MockInterface $order */
        $order = Mockery::mock(Order::class);
        $order->shouldAllowMockingMethod('payment');

        $order->shouldReceive('getAttribute')
            ->with('total_amount')
            ->andReturn(700.00);

        $paymentMock = Mockery::mock(Payment::class);
        $order->shouldReceive('payment->create')
            ->with(Mockery::on(function ($data) {
                return $data['status'] === PaymentStatusOld::SUCCESSFUL->value
                    && str_starts_with($data['tran_id'], 'TXN-')
                    && $data['amount'] === 700.00;
            }))
            ->andReturn($paymentMock);

        $result = $this->paymentService->createPayment($order, PaymentStatusOld::SUCCESSFUL->value);

        $this->assertInstanceOf(Payment::class, $result);
        $this->assertSame($paymentMock, $result);
    }

    public function test_create_payment_throws_for_invalid_legacy_status(): void
    {
        /** @var Order|Mockery\MockInterface $order */
        $order = Mockery::mock(Order::class);
        $order->shouldAllowMockingMethod('payment');
        $order->shouldNotReceive('payment->create');

        $this->expectException(InvalidArgumentException::class);

        $this->paymentService->createPayment($order, 'payment-pending');
    }
}
