<?php

declare(strict_types=1);

use Modules\Invoice\Models\Invoice;
use Modules\Order\Models\Order;
use Modules\Payment\Drivers\DBBLRocketDriver;
use Modules\Payment\Drivers\IslamicWalletDriver;
use Modules\Payment\Drivers\MCashDriver;
use Modules\Payment\Drivers\MYCashDriver;
use Modules\Payment\Drivers\Nagad\NagadDriver;
use Modules\Payment\Drivers\PortWalletDriver;
use Modules\Payment\Drivers\SureCashDriver;
use Modules\Payment\Drivers\Upay\UpayDriver;

return [
    'name' => 'Payment',

    'defaults' => [
        'provider' => env('PAYMENT_PROVIDER', 'bkash'),
        'currency' => env('PAYMENT_CURRENCY', 'BDT'),
    ],

    'providers' => [
        'nagad' => [
            'driver' => NagadDriver::class,
            'sandbox' => env('NAGAD_SANDBOX', true),
            'merchant_id' => env('NAGAD_MERCHANT_ID'),
            'merchant_number' => env('NAGAD_MERCHANT_NUMBER', ''),
            'public_key' => env('NAGAD_PUBLIC_KEY'),
            'private_key' => env('NAGAD_PRIVATE_KEY'),
            'callback_url' => env('NAGAD_CALLBACK_URL', 'http://your_domain/nagad/callback'),
            'timezone' => 'Asia/Dhaka',
            'response_type' => 'html',
        ],
        'upay' => [
            'driver' => UpayDriver::class,
            'sandbox' => env('UPAY_SANDBOX', false),
            'server_ip' => env('UPAY_SERVER_IP', ''),
            'merchant_id' => env('UPAY_MERCHANT_ID', ''),
            'merchant_key' => env('UPAY_MERCHANT_KEY', ''),
            'merchant_code' => env('UPAY_MERCHANT_CODE', ''),
            'merchant_name' => env('UPAY_MERCHANT_NAME', ''),
            'merchant_mobile' => env('UPAY_MERCHANT_MOBILE', ''),
            'merchant_city' => env('UPAY_MERCHANT_CITY', ''),
            'merchant_category_code' => env('UPAY_CATEGORY_CODE', ''),
            'redirect_url' => env('UPAY_CALLBACK_URL', ''),
        ],
        'dbblrocket' => [
            'driver' => DBBLRocketDriver::class,
        ],
        'islamicwallet' => [
            'driver' => IslamicWalletDriver::class,
        ],
        'mcash' => [
            'driver' => MCashDriver::class,
        ],
        'mycash' => [
            'driver' => MYCashDriver::class,
        ],
        'portwallet' => [
            'driver' => PortWalletDriver::class,
            'store_id',
            'store_password',
            'sandbox',
        ],
        'surecash' => [
            'driver' => SureCashDriver::class,
        ],
    ],

    // Invoice settings
    'invoice' => [
        'company_name' => env('INVOICE_COMPANY_NAME', 'Your Company'),
        'company_address' => env('INVOICE_COMPANY_ADDRESS', 'Your Address'),
        'tax_percentage' => env('INVOICE_TAX_PERCENTAGE', 10),

        'model' => env('INVOICE_MODEL', Invoice::class),
    ],

    'order' => [
        'model' => env('ORDER_MODEL', Order::class),
    ],
];
