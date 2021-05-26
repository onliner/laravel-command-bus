<?php

declare(strict_types=1);

namespace Onliner\Laravel\CommandBus\Factory;

use Illuminate\Contracts\Container\Container;
use Onliner\CommandBus\Remote\AMQP\AMQPTransport;
use Onliner\CommandBus\Remote\InMemory\InMemoryTransport;
use Onliner\CommandBus\Remote\Transport;
use Onliner\Laravel\CommandBus\Exception;

class TransportFactory
{
    private const DEFAULT = 'memory://';

    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return Transport
     */
    public function default(): Transport
    {
        return $this->createFromUrl(self::DEFAULT);
    }

    /**
     * @param string|array $config
     *
     * @return Transport|null
     */
    public function create($config): ?Transport
    {
        if (is_array($config) && array_key_exists('url', $config)) {
            return $this->createFromUrl($config['url'], $config['options'] ?? []);
        }

        if (is_string($config)) {
            if (filter_var($config, FILTER_VALIDATE_URL) !== false) {
                return $this->createFromUrl($config);
            }

            $instance = $this->container->get($config);

            return $instance instanceof Transport ? $instance : null;
        }

        return null;
    }

    /**
     * @param string $url
     * @param array  $options
     *
     * @return Transport
     */
    private function createFromUrl(string $url, array $options = []): Transport
    {
        switch (parse_url($url, PHP_URL_SCHEME)) {
            case 'amqp':
                return AMQPTransport::create($url, $options);
            case 'memory':
                return new InMemoryTransport();
            default:
                throw new Exception\BadTransportException($url);
        }
    }
}
