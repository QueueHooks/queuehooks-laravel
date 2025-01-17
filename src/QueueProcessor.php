<?php

namespace Queuehooks\QueuehooksLaravel;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

class QueueProcessor
{
    public static function handle($job, $jobId)
    {
        $job = unserialize($job);

        try {
            $result = $job->handle();
        } catch (\Exception $e) {
            if ($job->batchId) {
                $job->batch()->decrementPendingJobs($job->batchId, $jobId);
                $job->batch()->recordFailedJob($jobId, $e);

//                DB::connection(config('queue.batching.database'))
//                    ->table(config('queue.batching.table'))
//                    ->where('id', '=', $job->batchId)
//                    ->update([
//                        'pending_jobs' => DB::raw('pending_jobs - 1'),
//                        'failed_jobs'  => DB::raw('failed_jobs + 1')
//                    ]);
//
//                DB::connection(config('queue.batching.database'))
//                    ->table(config('queue.batching.table'))
//                    ->where('id', '=', $job->batchId)
//                    ->where('pending_jobs', '=', 0)
//                    ->update([
//                        'finished_at' => now()->timestamp
//                    ]);
            }
            throw $e;
            return;
        }

        if ($job->batchId) {

            $job->batch()->recordSuccessfulJob($jobId);
//            DB::connection(config('queue.batching.database'))
//                ->table(config('queue.batching.table'))
//                ->where('id', '=', $job->batchId)
//                ->update([
//                    'pending_jobs' => DB::raw('pending_jobs - 1')
//                ]);
//
//            DB::connection(config('queue.batching.database'))
//                ->table(config('queue.batching.table'))
//                ->where('id', '=', $job->batchId)
//                ->where('pending_jobs', '=', 0)
//                ->update([
//                    'finished_at' => now()->timestamp
//                ]);
        }

        return $result;
    }
}
