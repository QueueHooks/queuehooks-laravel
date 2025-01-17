<?php

namespace Queuehooks\QueuehooksLaravel;

use Illuminate\Support\ServiceProvider;

class QueueHooksServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/queuehooks.php',
            'queuehooks'
        );

        $this->loadRoutesFrom(__DIR__ . '/routes/queuehooks.php');
    }


    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/queuehooks.php' => config_path('queuehooks.php'),
        ], 'config');

        $manager = $this->app['queue'];

        $manager->addConnector('queuehooks', function () {
            return new QueueHooksConnector($this->app['events']);
        });
    }
}
