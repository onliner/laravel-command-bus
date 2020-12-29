<?php

declare(strict_types=1);

namespace Onliner\Laravel\CommandBus\Exception;

use Onliner\CommandBus\Exception\CommandBusException;

class UnknownSerializerException extends CommandBusException
{
    public function __construct(string $type)
    {
        parent::__construct(sprintf('Unknown serializer: %s.', $type));
    }
}
