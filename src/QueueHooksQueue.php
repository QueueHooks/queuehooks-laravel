<?php

namespace Queuehooks\QueuehooksLaravel;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Queue\Queue;
use Illuminate\Support\Facades\Http;
use Throwable;

class QueueHooksQueue extends Queue implements QueueContract
{
    /**
     * Create a new sync queue instance.
     *
     * @param bool $dispatchAfterCommit
     * @return void
     */
    public function __construct($dispatchAfterCommit = false)
    {
        $this->dispatchAfterCommit = $dispatchAfterCommit;
    }

    /**
     * Get the size of the queue.
     *
     * @param string|null $queue
     * @return int
     */
    public function size($queue = null)
    {
        $response = Http::withToken(config('queuehooks.token'))
            ->retry(3, 1000)
            ->post(config('queuehooks.server') . '/queue-size', ['queue' => $queue]);

        return $response->json('total');
    }

    /**
     * Push a new job onto the queue.
     *
     * @param string $job
     * @param mixed $data
     * @param string|null $queue
     * @return mixed
     *
     * @throws \Throwable
     */
    public function push($job, $data = '', $queue = null)
    {
        if ($this->shouldDispatchAfterCommit($job) &&
            $this->container->bound('db.transactions')) {
            return $this->container->make('db.transactions')->addCallback(
                fn() => $this->executeJob($job, $data, $queue)
            );
        }

        return $this->executeJob($job, $data, $queue);
    }


    protected function executeJob($job, $data = '', $queue = null)
    {
        $queue = $queue ?: $job->getQueue();

        if (empty($queue)) {
            if (config('queuehooks.default_queue')) {
                $queue = config('queuehooks.default_queue');
            } else {
                throw new \Exception('Queue name is required');
            }
        }

        if (!property_exists($job, 'nonce') || empty($job->nonce)) {
            $nonce = md5(uniqid());
        } else {
            $nonce = $job->nonce;
        }

        $response = Http::withToken(config('queuehooks.token'))
            ->retry(3, 1000)
            ->post(config('queuehooks.server'), [
                'nonce'       => $nonce,
                'payload'     => ['laravel_job' => serialize($job)],
                //queue job class name
                'description' => get_class($job),
                'queue'       => $queue,
            ]);

        return $response->ok() ? $response->json('id') : 0;
    }

    private function executeScheduledJob(Carbon $processAt, $job, $data, $queue)
    {
        $queue = $queue ?: $job->getQueue();

        if (empty($queue)) {
            if (config('queuehooks.default_queue')) {
                $queue = config('queuehooks.default_queue');
            } else {
                throw new \Exception('Queue name is required');
            }
        }

        if (!property_exists($job, 'nonce') || empty($job->nonce)) {
            $nonce = md5(uniqid());
        } else {
            $nonce = $job->nonce;
        }

        return Http::withToken(config('queuehooks.token'))
            ->retry(3, 1000)
            ->post(config('queuehooks.server'), [
                'nonce'       => $nonce,
                'payload'     => ['laravel_job' => serialize($job)],
                //queue job class name
                'description' => get_class($job),
                'queue'       => $queue,
                'schedule_at' => $processAt->toDateTimeString(),
            ])->ok() ? 1 : 0;
    }

    /**
     * Resolve a Sync job instance.
     *
     * @param string $payload
     * @param string $queue
     * @return \Illuminate\Queue\Jobs\SyncJob
     */
    protected function resolveJob($payload, $queue)
    {
        return new SyncJob($this->container, $payload, $this->connectionName, $queue);
    }

    /**
     * Raise the before queue job event.
     *
     * @param \Illuminate\Contracts\Queue\Job $job
     * @return void
     */
    protected function raiseBeforeJobEvent(Job $job)
    {
        if ($this->container->bound('events')) {
            $this->container['events']->dispatch(new JobProcessing($this->connectionName, $job));
        }
    }

    /**
     * Raise the after queue job event.
     *
     * @param \Illuminate\Contracts\Queue\Job $job
     * @return void
     */
    protected function raiseAfterJobEvent(Job $job)
    {
        if ($this->container->bound('events')) {
            $this->container['events']->dispatch(new JobProcessed($this->connectionName, $job));
        }
    }

    /**
     * Raise the exception occurred queue job event.
     *
     * @param \Illuminate\Contracts\Queue\Job $job
     * @param \Throwable $e
     * @return void
     */
    protected function raiseExceptionOccurredJobEvent(Job $job, Throwable $e)
    {
        if ($this->container->bound('events')) {
            $this->container['events']->dispatch(new JobExceptionOccurred($this->connectionName, $job, $e));
        }
    }

    /**
     * Handle an exception that occurred while processing a job.
     *
     * @param \Illuminate\Contracts\Queue\Job $queueJob
     * @param \Throwable $e
     * @return void
     *
     * @throws \Throwable
     */
    protected function handleException(Job $queueJob, Throwable $e)
    {
        $this->raiseExceptionOccurredJobEvent($queueJob, $e);

        $queueJob->fail($e);

        throw $e;
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param string $payload
     * @param string|null $queue
     * @param array $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        //
    }

    /**
     * Push a new job onto the queue after (n) seconds.
     *
     * @param \DateTimeInterface|\DateInterval|int $delay
     * @param string $job
     * @param mixed $data
     * @param string|null $queue
     * @return mixed
     * @throws \Exception
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        if ($delay instanceof Carbon) {
            return $this->executeScheduledJob($delay, $job, $data, $queue);
        }

        if (gettype($delay) === 'integer') {
            return $this->executeScheduledJob(Carbon::now()->addSeconds($delay), $job, $data, $queue);
        }

        if (gettype($delay) === 'string') {
            return $this->executeScheduledJob(Carbon::parse($delay), $job, $data, $queue);
        }

        throw new \Exception('Invalid delay type');
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param string|null $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        //
    }

    public function bulk($jobs, $data = '', $queue = null)
    {
        foreach ((array)$jobs as $job) {
            if (isset($job->delay)) {
                $this->later($job->delay, $job, $data, $queue);
            } else {
                $this->push($job, $data, $queue);
            }
        }
    }
}
