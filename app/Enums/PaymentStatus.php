<?php

declare(strict_types=1);

namespace Modules\Payment\Enums;

enum PaymentStatus: string
{
    case Pending = 'payment-pending';
    case Processing = 'payment-processing';
    case Success = 'payment-success';
    case Failed = 'payment-failed';
    case Reversal = 'payment-reversal';
    case Refunded = 'payment-refunded';
    case CashOnDelivery = 'payment-cash-on-delivery';
    case Cash = 'payment-cash';
    case Wallet = 'payment-wallet';
    case AwaitingForApproval = 'payment-awaiting-for-approval';
}
