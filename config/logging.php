<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],
        'lucky_gift' => [
            'driver' => 'single',
            'path' => storage_path('logs/lucky_gift.log'),
            'level' => env('LOG_LEVEL', 'debug'),

        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        'custom_log' => [
            'driver' => 'single',
            'path' => storage_path('logs/custom.log'),
            'level' => 'debug',
        ],


        'charisma' => [
            'driver' => 'single',
            'path' => storage_path('logs/charisma.log'),
            'level' => 'debug',
        ],
        'charisma_value' => [
            'driver' => 'daily',
            'path' => storage_path('logs/charisma-value-log.log'),
            'level' => 'debug',
            'days' => 14,
        ],
        'roomCup' => [
            'driver' => 'single',
            'path' => storage_path('logs/roomCup.log'),
            'level' => 'info',
        ],
        'payPal' => [
            'driver' => 'single',
            'path' => storage_path('logs/paypal.log'),
            'level' => 'info',
        ],
        'utd' => [
            'driver' => 'single',
            'path' => storage_path('logs/utd.log'),
            'level' => 'debug',
        ],
        'zigo_handler' => [
            'driver' => 'daily',
            'path' => storage_path('logs/zigo_handler.log'),
            'level' => 'debug',
            'days' => 14,
        ],
        'datetime' => [
            'driver' => 'single',
            'path' => storage_path('logs/datetime.log'),
            'level' => 'debug',
        ],
        'lucky_gift_receiver_issue' => [
            'driver' => 'daily',
            'path' => storage_path('logs/lucky_gift_receiver_issue.log'),
            'level' => 'debug',
            'days' => 14,
        ],
        'wallet' => [
            'driver' => 'daily',
            'path' => storage_path('logs/wallet.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],
        'chat' => [
            'driver' => 'daily',
            'path' => storage_path('logs/chat.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],
        'fairluck' => [
            'driver' => 'daily',
            'path' => storage_path('logs/fairluck.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],
        'payment' => [
            'driver' => 'daily',
            'path' => storage_path('logs/payment.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],
        'firebase' => [
            'driver' => 'daily',
            'path' => storage_path('logs/firebase.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],
    ],

];
