<?php

namespace Modules\Payment\Tests\Unit;

use Mockery;
use Modules\Invoice\Services\InvoiceService;
use Modules\Order\Models\Order;
use Modules\Order\Services\OrderService;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentService;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    protected PaymentService $paymentService;
    protected OrderService $mockOrderService;
    protected InvoiceService $mockInvoiceService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockOrderService = Mockery::mock(OrderService::class);
        $this->mockInvoiceService = Mockery::mock(InvoiceService::class);

        $this->paymentService = new PaymentService(
            $this->mockOrderService,
            $this->mockInvoiceService
        );
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCreatePaymentSuccessfully()
{
    // Create a mock of the Order model
    /** @var \Modules\Order\Models\Order|\Mockery\MockInterface $order */
    $order = Mockery::mock(Order::class);
    $order->shouldAllowMockingMethod('payment'); // Allow mocking the 'payment' relationship

    $order->shouldReceive('getAttribute')
        ->with('total_amount')
        ->andReturn(500.00);

    // Mock the payment relationship and the create() method
    $paymentMock = Mockery::mock(Payment::class);
    $order->shouldReceive('payment->create')
        ->with(Mockery::on(function ($data) {
            return $data['status'] === PaymentStatus::PENDING
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

}
