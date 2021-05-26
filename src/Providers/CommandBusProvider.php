<?php

declare(strict_types=1);

namespace Onliner\Laravel\CommandBus\Providers;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Onliner\CommandBus\Builder;
use Onliner\CommandBus\Dispatcher;
use Onliner\CommandBus\Remote\RemoteExtension;
use Onliner\CommandBus\Remote\Router;
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

    /**
     * @return void
     */
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

    /**
     * @return void
     */
    public function register(): void
    {
        $this->registerExtensions($this->config('extensions'));
        $this->registerMiddlewares($this->config('middlewares'));
        $this->registerRemote($this->config('remote'));
        $this->registerRetries($this->config('retries'));
        $this->registerDispatcher($this->config('handlers'));
    }

    /**
     * @param array $extensions
     *
     * @return void
     */
    private function registerExtensions(array $extensions): void
    {
        foreach ($extensions as $extension) {
            $this->app->tag($extension, [self::TAG_EXTENSION]);
        }
    }

    /**
     * @param array $middlewares
     *
     * @return void
     */
    private function registerMiddlewares(array $middlewares): void
    {
        foreach ($middlewares as $middleware) {
            $this->app->tag($middleware, [self::TAG_MIDDLEWARE]);
        }
    }

    /**
     * @param array $config
     *
     * @return void
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
     * @param array $config
     *
     * @return void
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
            $router = new Router($this->getTransportInstance($default, $transports));

            foreach ($config['routes'] ?? [] as $pattern => $key) {
                $router->add($pattern, $this->getTransportInstance($key, $transports));
            }

            return $router;
        });
    }

    /**
     * @param string $key
     * @param array $transports
     *
     * @return Transport
     */
    private function getTransportInstance(string $key, array $transports): Transport
    {
        if (!isset($transports[$key])) {
            throw new Exception\UnknownTransportException($key, array_keys($transports));
        }

        return $this->app->get($transports[$key]);
    }

    /**
     * Available config formats:
     *
     * [
     *   'foo' => \App\CommandBus\Transport\RedisTransport::class,
     *   'bar' => 'app.command_bus.transport.redis',
     *   'baz' => 'amqp://localhost:5672',
     *   'qux' => 'memory://',
     *   'quz' => [
     *     'url' => 'amqp://localhost:5672',
     *     'options' => [
     *       'exchange' => 'project',
     *     ],
     *   ],
     * ]
     *
     * @param string $key
     * @param mixed $config
     *
     * @return string
     */
    private function registerTransportConnection(string $key, $config): string
    {
        $name = sprintf('onliner.commandbus.transport.%s', $key);

        $this->app->singleton($name, function (Container $app) use ($key, $config) {
            if (!$transport = (new TransportFactory($app))->create($config)) {
                throw new Exception\InvalidTransportException($key);
            }

            return $transport;
        });

        return $name;
    }

    /**
     * @param array $serializer
     *
     * @return void
     */
    private function registerSerializer(array $serializer): void
    {
        $this->app->singleton(Serializer::class, function () use ($serializer) {
            $type = $serializer['type'] ?? SerializerFactory::DEFAULT;

            return SerializerFactory::create($type, $serializer['options'] ?? []);
        });
    }

    /**
     * @param array $config
     *
     * @return void
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
     * @param array $handlers
     *
     * @return void
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

    /**
     * @param string $section
     *
     * @return array
     */
    private function config(string $section): array
    {
        return $this->app->get('config')->get('commandbus.' . $section, []);
    }
}
