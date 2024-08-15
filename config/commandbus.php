<?php

declare(strict_types=1);

use Onliner\CommandBus\Retry\Policy;
use Onliner\Laravel\CommandBus\Factory\TransportFactory;

return [
    'remote' => [
        'enabled' => true,
        'serializer' => [
            'type' => 'native',
            'options' => [
                // 'key' => 'value',
            ],
        ],
        'transport' => [
            'default' => 'memory',
            'connections' => [
                'memory' => [
                    'url' => TransportFactory::DEFAULT,
                    'options' => [
                        // 'key' => 'value',
                    ],
                ],
                // 'amqp' => [
                //     'url' => 'amqp://guest:guest@localhost:5672/vhost',
                //     'options' => [
                //         'exchange' => 'commands',
                //         'durable' => true,
                //         'type' => 'topic',
                //     ],
                // ],
            ],
            // 'routes' => [
            //    '*' => 'memory',
            // ],
        ],
        'consumer' => [
            'options' => [
                // AMQPConsumer::OPTION_ATTEMPTS => 10,
                // AMQPConsumer::OPTION_INTERVAL => 1000,
            ],
            'queues' => [
                // 'pattern' => [
                //     'name' => 'my-queue',
                //     'durable' => true,
                //     'bindings' => [
                //         'exchange' => 'pattern',
                //     ],
                //     'args' => [
                //         Queue::MAX_PRIORITY => 3,
                //     ],
                // ],
            ],
        ],
        'local' => [
            // Command::class,
        ],
    ],
    'retries' => [
        'default' => Policy\ThrowPolicy::class,
        'policies' => [
            // Command::class => CustomPolicy::class,
        ],
    ],
    'handlers' => [
        // Command::class => Handler::class,
    ],
    'extensions' => [
        // CustomExtension::class,
    ],
    'middlewares' => [
        // CustomMiddleware::class,
    ],
];
