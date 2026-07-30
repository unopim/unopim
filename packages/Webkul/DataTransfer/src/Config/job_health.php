<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stall Timeout
    |--------------------------------------------------------------------------
    |
    | Seconds a running job may go without a heartbeat before it is treated as
    | dead. A worker killed by OOM or SIGKILL never reaches its failure handler,
    | so without this the job track stays in an active state forever and the
    | tracker screen polls a job that will never finish.
    |
    | Keep this comfortably above the slowest single batch: the heartbeat is
    | written from inside the batch loop, not only at batch boundaries.
    |
    */

    'stall_timeout' => (int) env('UNOPIM_JOB_STALL_TIMEOUT', 900),

    /*
    |--------------------------------------------------------------------------
    | Heartbeat Interval
    |--------------------------------------------------------------------------
    |
    | Minimum seconds between heartbeat writes. Throttled so a fast batch does
    | not issue one UPDATE per row.
    |
    */

    'heartbeat_interval' => (int) env('UNOPIM_JOB_HEARTBEAT_INTERVAL', 30),
];
