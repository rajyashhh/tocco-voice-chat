<?php

use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\RequestTerminated;
use Laravel\Octane\Events\TaskReceived;
use Laravel\Octane\Events\TickReceived;
use Laravel\Octane\Events\WorkerStarting;
use Laravel\Octane\Events\WorkerStopping;
use Laravel\Octane\Listeners\CollectGarbage;
use Laravel\Octane\Listeners\DisconnectFromDatabases;
use Laravel\Octane\Listeners\EnsureUploadedFilesAreValid;
use Laravel\Octane\Listeners\EnsureUploadedFilesCanBeMoved;
use Laravel\Octane\Listeners\FlushTemporaryContainerInstances;
use Laravel\Octane\Listeners\ReportException;
use Laravel\Octane\Listeners\StopWorkerIfNecessary;
use Laravel\Octane\Listeners\FlushAuthenticationState;
use Laravel\Octane\Listeners\FlushSessionState;
use Laravel\Octane\Listeners\FlushLocaleState;
use Laravel\Octane\Listeners\FlushQueuedCookies;
use Laravel\Octane\Octane;

return [

    /*
    |--------------------------------------------------------------------------
    | Octane Server
    |--------------------------------------------------------------------------
    */

    'server' => env('OCTANE_SERVER', 'swoole'),

    /*
    |--------------------------------------------------------------------------
    | Force HTTPS
    |--------------------------------------------------------------------------
    */

    'https' => env('OCTANE_HTTPS', false),

    /*
    |--------------------------------------------------------------------------
    | Octane Listeners
    |--------------------------------------------------------------------------
    |
    | هذه الـ listeners مهمة جداً لـ reset الـ state بين الـ requests
    | خاصة الـ Authentication state
    |
    */

    'listeners' => [
        WorkerStarting::class => [
            EnsureUploadedFilesAreValid::class,
            EnsureUploadedFilesCanBeMoved::class,
        ],

        RequestReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
            ...Octane::prepareApplicationForNextRequest(),
            FlushAuthenticationState::class,
            FlushSessionState::class,
            FlushLocaleState::class,
            FlushQueuedCookies::class,
        ],

        RequestTerminated::class => [
            FlushTemporaryContainerInstances::class,
            // DisconnectFromDatabases::class,
            CollectGarbage::class,
            ReportException::class,
            StopWorkerIfNecessary::class,
        ],

        TaskReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
        ],

        TickReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
        ],

        WorkerStopping::class => [
            //
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Warm Services
    |--------------------------------------------------------------------------
    |
    | هذه الـ services تبقى محملة في الذاكرة بين الـ requests
    |
    */

    'warm' => [
        ...Octane::defaultServicesToWarm(),
    ],

    /*
    |--------------------------------------------------------------------------
    | Flush Services
    |--------------------------------------------------------------------------
    |
    | هذه الـ services يتم إعادة تحميلها مع كل request
    | مهم جداً لتجنب مشاكل الـ state
    |
    */

    'flush' => [
        'auth',
        'auth.driver',
        'session',
        'session.store',
        'request',
    ],

    /*
    |--------------------------------------------------------------------------
    | Swoole Options
    |--------------------------------------------------------------------------
    */

    'swoole' => [
        'options' => [
            'worker_num' => env('OCTANE_WORKERS', swoole_cpu_num()),
            'task_worker_num' => env('OCTANE_TASK_WORKERS', min(4, (int) ceil(swoole_cpu_num() / 4))),
            'max_request' => env('OCTANE_MAX_REQUESTS', 500),
            'package_max_length' => 30 * 1024 * 1024,
            'http_parse_post' => true,
            'http_parse_cookie' => true,
            'enable_coroutine' => true,
            'log_level' => env('APP_DEBUG', false) ? 0 : 3,
            'daemonize' => false,
            'open_tcp_nodelay' => true,
            'enable_reuse_port' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Garbage Collection
    |--------------------------------------------------------------------------
    */

    'garbage' => env('OCTANE_GARBAGE_COLLECTION', 50),

    /*
    |--------------------------------------------------------------------------
    | Maximum Execution Time
    |--------------------------------------------------------------------------
    */

    'max_execution_time' => 30,

    /*
    |--------------------------------------------------------------------------
    | Tables
    |--------------------------------------------------------------------------
    */

    'tables' => [
        'example:1000' => [
            'name' => 'string:1000',
            'votes' => 'int',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'rows' => 1000,
        'bytes' => 10000,
    ],

];

