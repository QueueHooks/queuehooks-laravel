<?php

namespace Queuehooks\QueuehooksLaravel;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class QueueHooksExampleQueueJob implements ShouldQueue
{
    use Queueable;

    protected $payload;

    /**
     * Create a new job instance.
     */
    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        logger()->info('Test Queue Job executed successfully', [$this->payload]);
    }
}
