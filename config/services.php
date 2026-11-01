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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'kirimi' => [
        'user_code' => env('KIRIMI_USER_CODE', 'KMJB0W126'),
        'device_id' => env('KIRIMI_DEVICE_ID', 'D-9EGEG'),
        'secret' => env('KIRIMI_SECRET', '1ef98610fbcde02b51448a9bdc322a5cfb2801d08b7cb3d09e6f6630a19c2070'),
        'api_url' => env('KIRIMI_API_URL', 'https://api.kirimi.id/v1/send-message'),
        'override_phone' => env('KIRIMI_OVERRIDE_PHONE', null),
    ],

];
