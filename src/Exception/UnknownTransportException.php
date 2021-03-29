<?php

declare(strict_types=1);

namespace Onliner\Laravel\CommandBus\Exception;

use Onliner\CommandBus\Exception\CommandBusException;

class UnknownTransportException extends CommandBusException
{
    /**
     * @param string $key
     * @param array $available
     */
    public function __construct(string $key, array $available)
    {
        parent::__construct(sprintf('Unknown transport "%s". Available: %s', $key, implode(', ', $available)));
    }
}
