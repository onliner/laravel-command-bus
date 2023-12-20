<?php

declare(strict_types=1);

namespace Onliner\Laravel\CommandBus\Factory;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Onliner\CommandBus\Remote\AMQP\AMQPTransport;
use Onliner\CommandBus\Remote\Transport;
use Onliner\Laravel\CommandBus\Exception;

class TransportFactory
{
    public const DEFAULT = 'memory://memory';

    /**
     * @param Container $container
     */
    public function __construct(private Container $container)
    {
    }

    /**
     * @return Transport
     */
    public function default(): Transport
    {
        return $this->createFromUrl(self::DEFAULT);
    }

    /**
     * @param string $key
     * @param string|array $config
     *
     * @return Transport
     */
    public function create(string $key, string|array $config): Transport
    {
        if (is_array($config) && array_key_exists('url', $config)) {
            return $this->createFromUrl($config['url'], $config['options'] ?? []);
        }

        if (is_string($config)) {
            if (filter_var($config, FILTER_VALIDATE_URL) !== false) {
                return $this->createFromUrl($config);
            }

            $instance = $this->container->get($config);

            if (!$instance instanceof Transport) {
                throw new Exception\InvalidTransportException($key);
            }

            return $instance;
        }

        throw new InvalidArgumentException(sprintf('Invalid transport "%s" configuration.', $key));
    }

    /**
     * @param string $url
     * @param array  $options
     *
     * @return Transport
     */
    private function createFromUrl(string $url, array $options = []): Transport
    {
        return match (parse_url($url, PHP_URL_SCHEME)) {
            'amqp' => AMQPTransport::create($url, $options),
            'memory' => new Transport\MemoryTransport(),
            default => throw new Exception\BadTransportException($url),
        };
    }
}
