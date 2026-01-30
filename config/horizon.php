<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Horizon will be accessible from. If this
    | setting is null, Horizon will reside under the same domain as the
    | application. Otherwise, this value will serve as the subdomain.
    |
    */

    'domain' => env('HORIZON_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Horizon will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */

    'path' => env('HORIZON_PATH', 'horizon'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    |
    | This is the name of the Redis connection where Horizon will store the
    | meta information required for it to function. It includes the list
    | of supervisors, failed jobs, job metrics, and other information.
    |
    */

    'use' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Horizon Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will get attached onto each Horizon request, giving
    | you the chance to add your own middleware to this list or change any
    | of the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => [
        'web',
        \Laravel\Horizon\Http\Middleware\Authenticate::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    |
    | This option allows you to configure when the LongWaitDetected event
    | will be fired. Every connection / queue combination may have its
    | own, unique threshold (in seconds) before this event is fired.
    |
    */

    'waits' => [
        'redis:default' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times
    |--------------------------------------------------------------------------
    |
    | Here you can configure for how long (in minutes) you want Horizon to
    | persist the recent and failed jobs. Typically, recent jobs are kept
    | for one hour while all failed jobs are stored until you prune them
    | manually. Of course, you may change these values based on your needs.
    |
    */

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    |
    | Here you can configure how many snapshots should be kept to display in
    | the Horizon metrics graphs. This will get used in combination with
    | Horizon's `horizon:snapshot` schedule to define how long to retain
    | metrics data.
    |
    */

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    |
    | When this option is enabled, Horizon's "terminate" command will not
    | wait on all of the workers to finish executing their current jobs
    | before terminating. This option should be enabled if your workers
    | are configured to gracefully handle "TERM" signals to finish their
    | current jobs and exit.
    |
    */

    'fast_termination' => false,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    |
    | This value describes the maximum amount of memory (in MB) that a worker
    | may consume before it is terminated and restarted. You should set this
    | value according to the resources available to your server as well as
    | your job's memory requirements.
    |
    */

    'memory_limit' => 64,

    /*
    |--------------------------------------------------------------------------
    | Queue Balance Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure which queues and connections you wish to balance
    | as well as the strategy used to balance them. Horizon supports three
    | balancing strategies: "simple", "auto", and "false".
    |
    | See: https://laravel.com/docs/horizon#balancing-strategies
    |
    */

    'balance' => 'auto',

    'balance_max_shifts' => 1,

    'balance_cooldown' => 3,

    /*
    |--------------------------------------------------------------------------
    | Job Batching
    |--------------------------------------------------------------------------
    |
    | The following options configure the batching feature. You are free to
    | adjust these options based on the requirements of your application.
    |
    */

    'batching' => [
        'trim_batches' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    |
    | When this option is enabled, Horizon's "terminate" command will not
    | wait on all of the workers to finish executing their current jobs
    | before terminating. This option should be enabled if your workers
    | are configured to gracefully handle "TERM" signals to finish their
    | current jobs and exit.
    |
    */

    'darkmode' => true,

];
