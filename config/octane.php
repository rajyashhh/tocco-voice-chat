<?php

use Laravel\Octane\Contracts\OperationTerminated;
use Laravel\Octane\Events\RequestHandled;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\RequestTerminated;
use Laravel\Octane\Events\TaskReceived;
use Laravel\Octane\Events\TaskTerminated;
use Laravel\Octane\Events\TickReceived;
use Laravel\Octane\Events\TickTerminated;
use Laravel\Octane\Events\WorkerErrorOccurred;
use Laravel\Octane\Events\WorkerStarting;
use Laravel\Octane\Events\WorkerStopping;
use Laravel\Octane\Listeners\CloseMonologHandlers;
use Laravel\Octane\Listeners\EnsureUploadedFilesAreValid;
use Laravel\Octane\Listeners\EnsureUploadedFilesCanBeMoved;
use Laravel\Octane\Listeners\FlushOnce;
use Laravel\Octane\Listeners\FlushTemporaryContainerInstances;
use Laravel\Octane\Listeners\ReportException;
use Laravel\Octane\Listeners\StopWorkerIfNecessary;
use Laravel\Octane\Octane;

return [

    /*
    |--------------------------------------------------------------------------
    | Octane Server
    |--------------------------------------------------------------------------
    |
    | This value is the type of server that Octane will be running on.
    |
    */

    'server' => env('OCTANE_SERVER', 'swoole'),

    /*
    |--------------------------------------------------------------------------
    | Octane Port
    |--------------------------------------------------------------------------
    |
    | This value is the port that Octane will be listening on.
    |
    */

    'port' => env('OCTANE_PORT', 8000),

    /*
    |--------------------------------------------------------------------------
    | Octane Workers
    |--------------------------------------------------------------------------
    |
    | The number of workers that should be assigned to Octane. By default,
    | this will be the number of CPU cores available on the machine.
    |
    */

    'workers' => env('OCTANE_WORKERS'),

    /*
    |--------------------------------------------------------------------------
    | Octane Max Requests
    |--------------------------------------------------------------------------
    |
    | The number of requests an Octane worker will process before being
    | recycled. This is useful for preventing memory leaks.
    |
    */

    'max_requests' => env('OCTANE_MAX_REQUESTS', 500),

    /*
    |--------------------------------------------------------------------------
    | Octane Tick Frequency
    |--------------------------------------------------------------------------
    |
    | The number of milliseconds between each "tick" of the Octane server.
    |
    */

    'tick_frequency' => env('OCTANE_TICK_FREQUENCY', 1000),

    /*
    |--------------------------------------------------------------------------
    | Octane Cache Table Size
    |--------------------------------------------------------------------------
    |
    | The size of the Swoole table used for caching.
    |
    */

    'cache_table_size' => env('OCTANE_CACHE_TABLE_SIZE', 32000),

    /*
    |--------------------------------------------------------------------------
    | Octane Listeners
    |--------------------------------------------------------------------------
    |
    | All of the event listeners for Octane's events are defined below. These
    | listeners are responsible for resetting your application's state for
    | the next request. You may even add your own listeners to the list.
    |
    | NOTE: This MUST stay the FULL default Octane listener list. mergeConfigFrom
    | does a shallow array_merge on the top-level `octane` key, so defining the
    | `listeners` key here REPLACES the package default wholesale — it is not
    | deep-merged. A previous partial list (RequestTerminated only) silently
    | dropped FlushLogContext (and every other per-request reset listener),
    | which leaked the request_id (Log::withContext) from the RequestId
    | middleware across requests on the same Swoole worker — corrupting the
    | correlation id. We therefore copy the package default in full and only
    | ADD our DisconnectIdleDbConnections under RequestTerminated.
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
            //
        ],

        RequestHandled::class => [
            //
        ],

        RequestTerminated::class => [
            // FlushUploadedFiles::class,

            // App addition: disconnect idle DB connections after each request to
            // prevent connection leaks under Octane/Swoole long-lived workers.
            \App\Listeners\DisconnectIdleDbConnections::class,
        ],

        TaskReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
            //
        ],

        TaskTerminated::class => [
            //
        ],

        TickReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
            //
        ],

        TickTerminated::class => [
            //
        ],

        OperationTerminated::class => [
            FlushOnce::class,
            FlushTemporaryContainerInstances::class,
            // DisconnectFromDatabases::class,
            // CollectGarbage::class,
        ],

        WorkerErrorOccurred::class => [
            ReportException::class,
            StopWorkerIfNecessary::class,
        ],

        WorkerStopping::class => [
            CloseMonologHandlers::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Octane Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware are applied to all requests processed by Octane.
    |
    */

    'middleware' => [
        // 'Illuminate\Http\Middleware\TrustProxies',
    ],

    /*
    |--------------------------------------------------------------------------
    | Octane Warm
    |--------------------------------------------------------------------------
    |
    | These classes will be instantiated and registered in the container
    | when Octane starts. This is useful for warming up the application.
    |
    */

    'warm' => [
        // 'App\Models\User',
    ],

    /*
    |--------------------------------------------------------------------------
    | Swoole Options
    |--------------------------------------------------------------------------
    |
    | Configure Swoole-specific server options here. The log_level is set
    | to ERROR (5) to suppress harmless "Unsupported SSL request" warnings
    | that occur when clients attempt HTTPS on the plain HTTP port.
    |
    | Log Levels: 0=DEBUG, 1=TRACE, 2=INFO, 3=NOTICE, 4=WARNING, 5=ERROR
    |
    */

    'swoole' => [
        'options' => [
            'log_level' => env('SWOOLE_LOG_LEVEL', 5),
        ],
    ],

];
