<?php

declare(strict_types=1);

namespace Onliner\Laravel\CommandBus\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Onliner\CommandBus\Builder;
use Onliner\CommandBus\Dispatcher;
use Onliner\CommandBus\Remote\RemoteExtension;
use Onliner\CommandBus\Remote\Serializer;
use Onliner\CommandBus\Remote\Transport;
use Onliner\CommandBus\Retry\RetryExtension;
use Onliner\Laravel\CommandBus\Console;
use Onliner\Laravel\CommandBus\Factory\SerializerFactory;
use Onliner\Laravel\CommandBus\Factory\TransportFactory;

class CommandBusProvider extends ServiceProvider
{
    private const CONFIG_FILENAME = 'commandbus.php';
    private const
        TAG_EXTENSION  = 'commandbus.extension',
        TAG_MIDDLEWARE = 'commandbus.middleware'
    ;

    /**
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/' . self::CONFIG_FILENAME => $this->app->configPath(self::CONFIG_FILENAME),
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

        $this->app->singleton(Serializer::class, function () use ($config) {
            $type = $config['serializer']['type'] ?? SerializerFactory::DEFAULT;

            return SerializerFactory::create($type, $config['serializer']['options'] ?? []);
        });

        $this->app->singleton(Transport::class, function () use ($config) {
            $dsn = $config['transport']['dsn'] ?? TransportFactory::DEFAULT;

            return TransportFactory::create($dsn, $config['transport']['options'] ?? []);
        });

        $this->app->singleton(RemoteExtension::class, function (Application $app) use ($config) {
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
    private function registerRetries(array $config): void
    {
        $this->app->singleton(RetryExtension::class, function (Application $app) use ($config) {
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
        $this->app->singleton(Dispatcher::class, function (Application $app) use ($handlers) {
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
