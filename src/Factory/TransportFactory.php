<?php

declare(strict_types=1);

namespace Onliner\Laravel\CommandBus\Factory;

use Onliner\CommandBus\Remote\AMQP\AMQPTransport;
use Onliner\CommandBus\Remote\InMemory\InMemoryTransport;
use Onliner\CommandBus\Remote\Transport;
use Onliner\Laravel\CommandBus\Exception;

class TransportFactory
{
    public const DEFAULT = 'memory://';

    /**
     * @param string $dsn
     * @param array  $options
     *
     * @return Transport
     */
    public static function create(string $dsn, array $options = []): Transport
    {
        switch (parse_url($dsn, PHP_URL_SCHEME)) {
            case 'amqp':
                return AMQPTransport::create($dsn, $options);
            case 'memory':
                return new InMemoryTransport();
            default:
                throw new Exception\UnknownTransportException($dsn);
        }
    }
}
