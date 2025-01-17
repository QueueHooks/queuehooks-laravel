<?php

return [
    'default_queue' => env('QUEUEHOOKS_DEFAULT_QUEUE', 'default'),
    'server'        => env('QUEUEHOOKS_SERVER', 'https://ingest.queuehooks.com'),
    'token'         => env('QUEUEHOOKS_TOKEN', 'your-token-here'),
];
