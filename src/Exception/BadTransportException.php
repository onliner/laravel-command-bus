<?php

declare(strict_types=1);

namespace Onliner\Laravel\CommandBus\Exception;

use Onliner\CommandBus\Exception\CommandBusException;

class BadTransportException extends CommandBusException
{
    /**
     * @param string $url
     */
    public function __construct(string $url)
    {
        parent::__construct(sprintf('Bad transport URL: %s.', $url));
    }
}
