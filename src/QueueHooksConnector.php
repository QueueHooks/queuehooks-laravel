<?php

namespace Queuehooks\QueuehooksLaravel;

use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Support\Arr;

class QueueHooksConnector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param array $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        return new QueueHooksQueue(Arr::get($config, 'queue'), $config['after_commit'] ?? null);
    }
}
