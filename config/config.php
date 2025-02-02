<?php

$apiDomainSSLCZ = env('SSLCOMMERZ_SANDBOX') ? "https://sandbox.sslcommerz.com" : "https://securepay.sslcommerz.com";

return [
    'name' => 'Payment',

    'defaults' => [
        'provider' => env('PAYMENT_PROVIDER', 'bkash'),
        'currency' => env('PAYMENT_CURRENCY', 'BDT'),
    ],

    'providers' => [
        'sslcommerz' => [
            'driver' => \Modules\Payment\Drivers\SSLCommerz\SSLCommerzDriver::class,
            'sandbox' => env('SSLCOMMERZ_SANDBOX', true),
            'apiCredentials' => [
                'store_id' => env("SSLCOMMERZ_STORE_ID"),
                'store_password' => env("SSLCOMMERZ_STORE_PASSWORD"),
            ],
            'apiUrl' => [
                'make_payment' => "/gwprocess/v4/api.php",
                'transaction_status' => "/validator/api/merchantTransIDvalidationAPI.php",
                'order_validate' => "/validator/api/validationserverAPI.php",
                'refund_payment' => "/validator/api/merchantTransIDvalidationAPI.php",
                'refund_status' => "/validator/api/merchantTransIDvalidationAPI.php",
            ],
            'apiDomain' => $apiDomainSSLCZ,
            'connect_from_localhost' => env("IS_LOCALHOST", false),
            'success_url' => '/success',
            'failed_url' => '/fail',
            'cancel_url' => '/cancel',
            'ipn_url' => '/ipn',
        ],
        'bkash' => [
            'driver' => \Modules\Payment\Drivers\BkashDriver::class,
            'app_key' => env('BKASH_APP_KEY'),
            'app_secret' => env('BKASH_APP_SECRET'),
            'sandbox' => env('BKASH_SANDBOX', true),
        ],
        'nagad' => [
            'driver' => \Modules\Payment\Drivers\Nagad\NagadDriver::class,
            'sandbox' => env('NAGAD_SANDBOX', true),
            'merchant_id' => env('NAGAD_MERCHANT_ID'),
            "merchant_number" => env("NAGAD_MERCHANT_NUMBER", ""),
            'public_key' => env('NAGAD_PUBLIC_KEY'),
            'private_key' => env('NAGAD_PRIVATE_KEY'),
            "callback_url" => env("NAGAD_CALLBACK_URL", "http://your_domain/nagad/callback"),
            'timezone' => 'Asia/Dhaka',
            "response_type" => "html",
        ],
        'upay' => [
            'driver' => \Modules\Payment\Drivers\Upay\UpayDriver::class,
            "sandbox" => env("UPAY_SANDBOX", false),
            "server_ip" => env("UPAY_SERVER_IP", ""),
            "merchant_id" => env("UPAY_MERCHANT_ID", ""),
            "merchant_key" => env("UPAY_MERCHANT_KEY", ""),
            "merchant_code" => env("UPAY_MERCHANT_CODE", ""),
            "merchant_name" => env("UPAY_MERCHANT_NAME", ""),
            "merchant_mobile" => env("UPAY_MERCHANT_MOBILE", ""),
            "merchant_city" => env("UPAY_MERCHANT_CITY", ""),
            "merchant_category_code" => env("UPAY_CATEGORY_CODE", ""),
            "redirect_url" => env("UPAY_CALLBACK_URL", ""),
        ],
        'dbblrocket' => [
            'driver' => \Modules\Payment\Drivers\DBBLRocketDriver::class
        ],
        'islamicwallet' => [
            'driver' => \Modules\Payment\Drivers\IslamicWalletDriver::class
        ],
        'mcash' => [
            'driver' => \Modules\Payment\Drivers\MCashDriver::class
        ],
        'mycash' => [
            'driver' => \Modules\Payment\Drivers\MYCashDriver::class
        ],
        'portwallet' => [
            'driver' => \Modules\Payment\Drivers\PortWalletDriver::class,
            'store_id',
            'store_password',
            'sandbox',
        ],
        'surecash' => [
            'driver' => \Modules\Payment\Drivers\SureCashDriver::class
        ],
    ],

    // Invoice settings
    'invoice' => [
        'company_name' => env('INVOICE_COMPANY_NAME', 'Your Company'),
        'company_address' => env('INVOICE_COMPANY_ADDRESS', 'Your Address'),
        'tax_percentage' => env('INVOICE_TAX_PERCENTAGE', 10),

        'model' => env('INVOICE_MODEL', \Modules\Invoice\Models\Invoice::class)
    ],

    'order' => [
        'model' => env('ORDER_MODEL', \Modules\Order\Models\Order::class)
    ]
];
