<?php

declare(strict_types=1);

return [
    'mezzio-swoole' => [
        'enable_coroutine' => true,
        'swoole-http-server' => [
            'host' => '0.0.0.0',
            'mode' => SWOOLE_PROCESS,
            'port' => 8080,
            'options' => [
                'worker_num'      => 2,          // The number of HTTP Server Workers
                'task_worker_num' => 2,          // The number of Task Workers
                'task_enable_coroutine' => true, // optional to turn on task coroutine support
            ],
        ],
    ],
];
