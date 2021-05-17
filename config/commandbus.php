<?php

declare(strict_types=1);

use Onliner\CommandBus\Retry\Policy;

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
            'dsn' => 'memory://',
            'options' => [
                // 'key' => 'value',
            ],
        ],
        'consumer' => [
            'options' => [
                // AMQPConsumer::OPTION_ATTEMPTS => 10,
                // AMQPConsumer::OPTION_INTERVAL => 1000,
            ],
            'queues' => [
                // 'pattern' => [
                //     'durable' => true,
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
