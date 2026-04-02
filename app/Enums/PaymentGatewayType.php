<?php

declare(strict_types=1);

namespace Modules\Payment\Enums;

enum PaymentGatewayType: string
{
    case Stripe = 'STRIPE';
    case CashOnDelivery = 'CASH_ON_DELIVERY';
    case Cash = 'CASH';
    case FullWalletPayment = 'FULL_WALLET_PAYMENT';
    case Paypal = 'PAYPAL';
    case Razorpay = 'RAZORPAY';
    case Mollie = 'MOLLIE';
    case Sslcommerz = 'SSLCOMMERZ';
    case Paystack = 'PAYSTACK';
    case Xendit = 'XENDIT';
    case Iyzico = 'IYZICO';
    case Bkash = 'BKASH';
    case Paymongo = 'PAYMONGO';
    case Flutterwave = 'FLUTTERWAVE';
}
