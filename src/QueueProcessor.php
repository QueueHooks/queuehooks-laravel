<?php

namespace Queuehooks\QueuehooksLaravel;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

class QueueProcessor
{
    public static function handle($job)
    {
        $job = unserialize($job);

        $result = $job->handle();

        if ($job->batchId) {
            DB::connection(config('queue.batching.database'))
                ->table(config('queue.batching.table'))
                ->where('id', '=', $job->batchId)
                ->update([
                    'pending_jobs' => DB::raw('pending_jobs - 1')
                ]);

            DB::connection(config('queue.batching.database'))
                ->table(config('queue.batching.table'))
                ->where('id', '=', $job->batchId)
                ->where('pending_jobs', '=', 0)
                ->update([
                    'finished_at' => now()->timestamp
                ]);
        }

        return $result;
    }
}