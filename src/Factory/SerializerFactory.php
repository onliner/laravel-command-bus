<?php

declare(strict_types=1);

namespace Onliner\Laravel\CommandBus\Factory;

use Onliner\CommandBus\Remote\Serializer;
use Onliner\Laravel\CommandBus\Exception;

class SerializerFactory
{
    public const DEFAULT = 'native';

    /**
     * @param string $type
     * @param array  $options
     *
     * @return Serializer
     */
    public static function create(string $type, array $options = []): Serializer
    {
        switch ($type) {
            case 'native':
                return new Serializer\NativeSerializer();
            default:
                throw new Exception\UnknownSerializerException($type);
        }
    }
}
