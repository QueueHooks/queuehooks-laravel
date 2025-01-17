<?php

use Illuminate\Support\Facades\Route;

Route::post('queuehooks/ingest', [
    \Queuehooks\QueuehooksLaravel\QueueHooksController::class,
    'ingest'
])->name('queuehooks.ingest');
