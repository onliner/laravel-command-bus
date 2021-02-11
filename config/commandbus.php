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
                // 'key' => 'value',
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
