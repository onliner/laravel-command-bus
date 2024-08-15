<?php

declare(strict_types=1);

namespace Onliner\Laravel\CommandBus\Factory;

use Onliner\CommandBus\Remote\Serializer;
use Onliner\Laravel\CommandBus\Exception;

class SerializerFactory
{
    public const DEFAULT = 'native';

    public static function create(string $type, array $options = []): Serializer
    {
        return match ($type) {
            'native' => new Serializer\NativeSerializer(),
            default => throw new Exception\UnknownSerializerException($type),
        };
    }
}
