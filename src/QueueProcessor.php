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
                $job->batch()->recordFailedJob($jobId, $e);
            }

            throw $e;
        }

        if ($job->batchId) {
            $job->batch()->recordSuccessfulJob($jobId);
        }

        return $result;
    }
}
