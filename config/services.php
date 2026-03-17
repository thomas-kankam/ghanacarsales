<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun'  => [
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme'   => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses'      => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'paystack' => [
        'public_key'     => env('PAYSTACK_PUBLIC_KEY', 'pk_test_a92b460015192324c3073e5fbe9888ad6caaacca'),
        'secret_key'     => env('PAYSTACK_SECRET_KEY', 'sk_test_a5679995ef57939e9410a291ac015ea679c4bb72'),
        'payment_url'    => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),
        'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET'),
    ],

    'mnotify' => [
        'api_key' => env('MNOTIFY_API_KEY', 'yFasC3yZysO0BrCOLtc27I9vs'),
        'from'    => env('MNOTIFY_FROM', 'GhCarSales'),
    ],
];
