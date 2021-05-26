<?php

declare(strict_types=1);

namespace Onliner\Laravel\CommandBus\Exception;

use Onliner\CommandBus\Exception\CommandBusException;

class InvalidTransportException extends CommandBusException
{
    /**
     * @param string $key
     */
    public function __construct(string $key)
    {
        parent::__construct(sprintf('Invalid transport "%s" configuration.', $key));
    }
}
