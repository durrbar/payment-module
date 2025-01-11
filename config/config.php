<?php

return [
    'name' => 'Payment',

    'default_provider' => env('PAYMENT_GATEWAY', 'stripe'),

    'gateways' => [
        'sslcommerz' => [
            'api_key' => env('SSLCOMMERZ_API_KEY'),
            'api_secret' => env('SSLCOMMERZ_API_SECRET'),
            'sandbox' => env('SSLCOMMERZ_SANDBOX', true),
        ],
        'bkash' => [
            'app_key' => env('BKASH_APP_KEY'),
            'app_secret' => env('BKASH_APP_SECRET'),
            'sandbox' => env('BKASH_SANDBOX', true),
        ],
        'nagad' => [
            'merchant_id' => env('NAGAD_MERCHANT_ID'),
            'public_key' => env('NAGAD_PUBLIC_KEY'),
            'private_key' => env('NAGAD_PRIVATE_KEY'),
            'sandbox' => env('NAGAD_SANDBOX', true),
        ],
        'paypal' => [
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'client_secret' => env('PAYPAL_CLIENT_SECRET'),
            'sandbox' => env('PAYPAL_SANDBOX', true),
        ],
        'stripe' => [
            'api_key' => env('STRIPE_API_KEY'),
        ],
    ],

    // Invoice settings
    'invoice' => [
        'company_name' => env('INVOICE_COMPANY_NAME', 'Your Company'),
        'company_address' => env('INVOICE_COMPANY_ADDRESS', 'Your Address'),
        'tax_percentage' => env('INVOICE_TAX_PERCENTAGE', 10),
    ],
];
