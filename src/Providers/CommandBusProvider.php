<?php

declare(strict_types=1);

namespace Onliner\Laravel\CommandBus\Providers;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Onliner\CommandBus\Builder;
use Onliner\CommandBus\Dispatcher;
use Onliner\CommandBus\Remote\RemoteExtension;
use Onliner\CommandBus\Remote\Serializer;
use Onliner\CommandBus\Remote\Transport;
use Onliner\CommandBus\Retry\RetryExtension;
use Onliner\Laravel\CommandBus\Console;
use Onliner\Laravel\CommandBus\Exception;
use Onliner\Laravel\CommandBus\Factory\SerializerFactory;
use Onliner\Laravel\CommandBus\Factory\TransportFactory;

class CommandBusProvider extends ServiceProvider
{
    private const CONFIG_FILENAME = 'commandbus.php';
    private const
        TAG_EXTENSION  = 'onliner.commandbus.extension',
        TAG_MIDDLEWARE = 'onliner.commandbus.middleware'
    ;

    public function boot(): void
    {
        $configPath = $this->app->basePath('config') . DIRECTORY_SEPARATOR . self::CONFIG_FILENAME;

        $this->publishes([
            __DIR__ . '/../../config/' . self::CONFIG_FILENAME => $configPath,
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\CommandBusProcessCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->app->tag($this->config('extensions'), [self::TAG_EXTENSION]);
        $this->app->tag($this->config('middlewares'), [self::TAG_MIDDLEWARE]);

        $this->registerRemote($this->config('remote'));
        $this->registerRetries($this->config('retries'));
        $this->registerDispatcher($this->config('handlers'));
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerRemote(array $config): void
    {
        if (!($config['enabled'] ?? false)) {
            return;
        }

        $this->registerTransports($config['transport'] ?? []);
        $this->registerSerializer($config['serializer'] ?? []);

        $this->app->singleton(RemoteExtension::class, function (Container $app) use ($config) {
            $extension = new RemoteExtension($app->get(Transport::class), $app->get(Serializer::class));
            $extension->local(...($config['local'] ?? []));

            return $extension;
        });

        $this->app->tag(RemoteExtension::class, [self::TAG_EXTENSION]);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerTransports(array $config): void
    {
        $transports = [];

        foreach ($config['connections'] ?? [] as $key => $connection) {
            $transports[$key] = $this->registerTransportConnection($key, $connection);
        }

        $this->app->singleton(Transport::class, function (Container $app) use ($config, $transports) {
            if (empty($transports)) {
                return (new TransportFactory($app))->default();
            }

            $default = $config['default'] ?? array_key_first($transports);
            $transport = new Transport\MultiTransport($this->getTransportInstance($default, $transports));

            foreach ($config['routes'] ?? [] as $pattern => $key) {
                $transport->add($pattern, $this->getTransportInstance($key, $transports));
            }

            return $transport;
        });
    }

    /**
     * @param array<string, string> $transports
     */
    private function getTransportInstance(string $key, array $transports): Transport
    {
        if (!isset($transports[$key])) {
            throw new Exception\UnknownTransportException($key, array_keys($transports));
        }

        return $this->app->get($transports[$key]);
    }

    private function registerTransportConnection(string $key, array|string $config): string
    {
        $name = sprintf('onliner.commandbus.transport.%s', $key);

        $this->app->singleton($name, fn (Container $app) => (new TransportFactory($app))->create($key, $config));

        return $name;
    }

    /**
     * @param array<string, mixed> $serializer
     */
    private function registerSerializer(array $serializer): void
    {
        $this->app->singleton(Serializer::class, function () use ($serializer) {
            $type = $serializer['type'] ?? SerializerFactory::DEFAULT;

            return SerializerFactory::create($type, $serializer['options'] ?? []);
        });
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerRetries(array $config): void
    {
        $this->app->singleton(RetryExtension::class, function (Container $app) use ($config) {
            $default = isset($config['default']) ? $app->get($config['default']) : null;
            $extension = new RetryExtension($default);

            foreach ($config['policies'] ?? [] as $command => $policy) {
                $extension->policy($command, $app->get($policy));
            }

            return $extension;
        });

        $this->app->tag(RetryExtension::class, [self::TAG_EXTENSION]);
    }

    /**
     * @param array<string, string> $handlers
     */
    private function registerDispatcher(array $handlers): void
    {
        $this->app->singleton(Dispatcher::class, function (Container $app) use ($handlers) {
            $builder = new Builder();

            foreach ($handlers as $command => $class) {
                $builder->handle($command, function ($message, $context) use ($class, $app) {
                    $handler = $app->get($class);
                    $handler($message, $context);
                });
            }

            foreach ($app->tagged(self::TAG_EXTENSION) as $extension) {
                $builder->use($extension);
            }

            foreach ($app->tagged(self::TAG_MIDDLEWARE) as $middleware) {
                $builder->middleware($middleware);
            }

            return $builder->build();
        });
    }

    private function config(string $section): array
    {
        return $this->app->get('config')->get('commandbus.' . $section, []);
    }
}
