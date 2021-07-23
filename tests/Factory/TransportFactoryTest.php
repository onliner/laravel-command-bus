<?php

declare(strict_types=1);

namespace Factory;

use Onliner\CommandBus\Remote\InMemory\InMemoryTransport;
use Onliner\Laravel\CommandBus\Exception\UnknownTransportException;
use Onliner\Laravel\CommandBus\Factory\TransportFactory;
use PHPUnit\Framework\TestCase;

final class TransportFactoryTest extends TestCase
{
    public function testInMemoryTransportCreated(): void
    {
        $transport = TransportFactory::create(TransportFactory::DEFAULT);
        self::assertInstanceOf(InMemoryTransport::class, $transport);
    }

    public function testExceptionThrowsWhenTransportDsnIsInvalid(): void
    {
        self::expectException(UnknownTransportException::class);
        TransportFactory::create('memory://');
    }
}
