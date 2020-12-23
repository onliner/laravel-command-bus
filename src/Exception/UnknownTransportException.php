<?php

declare(strict_types=1);

namespace Onliner\Laravel\CommandBus\Exception;

use Onliner\CommandBus\Exception\CommandBusException;

class UnknownTransportException extends CommandBusException
{
    public function __construct(string $dsn)
    {
        parent::__construct(sprintf('Unknown transport: %s.', $dsn));
    }
}
