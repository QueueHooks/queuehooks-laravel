<?php

namespace Queuehooks\QueuehooksLaravel;

use Illuminate\Support\Arr;

class QueueHooksController
{
    public function ingest(QueueHooksIngestRequest $request)
    {
        $payload = $request->input('payload');

        $results = QueueProcessor::handle(Arr::get($payload, 'laravel_job'), $request->input('id'));

        return response()->json([
            'status' => 'success',
            'result' => $results,
        ]);
    }
}
