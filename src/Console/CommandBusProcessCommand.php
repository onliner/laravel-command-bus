<?php

declare(strict_types=1);

namespace Onliner\Laravel\CommandBus\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Onliner\CommandBus\Dispatcher;
use Onliner\CommandBus\Remote\AMQP\Queue;
use Onliner\CommandBus\Remote\Consumer;
use Onliner\CommandBus\Remote\Transport;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CommandBusProcessCommand extends Command
{
    protected $name = 'commands:process';
    protected $description = '';

    private ?Consumer $consumer = null;

    /**
     * @return array<array>
     */
    protected function getArguments(): array
    {
        return [
            ['pattern', InputArgument::REQUIRED, 'Routing pattern to subscribe'],
        ];
    }

    /**
     * @return array<array>
     */
    protected function getOptions(): array
    {
        return [
            ['user', 'u', InputOption::VALUE_OPTIONAL, 'User for which run workers'],
            ['exchange', 'e', InputOption::VALUE_OPTIONAL, 'Exchange to bind consumer queue'],
        ];
    }

    public function handle(Dispatcher $dispatcher, Transport $transport): int
    {
        $this->setupUser();
        $this->subscribeSignals();

        $config = (array) Config::get('commandbus.remote.consumer');

        $pattern = $this->argument('pattern');

        $options = $config['queues'][$pattern] ?? [
            'durable' => true,
        ];

        $options['pattern'] = $pattern;

        if (!isset($options['bindings'])) {
            $options['bindings'] = $this->option('exchange') ?: $this->getDefaultExchange();
        }

        $this->consumer = $transport->consume();
        $this->consumer->consume(Queue::create($options));
        $this->consumer->run($dispatcher, $config['options'] ?? []);

        return 0;
    }

    private function setupUser(): void
    {
        $user = $this->option('user');

        if (empty($user)) {
            return;
        }

        $data = ctype_digit($user) ? posix_getpwuid((int) $user) : posix_getpwnam($user);

        posix_setgid($data['gid']);
        posix_setuid($data['uid']);
    }

    private function subscribeSignals(): void
    {
        pcntl_async_signals(true);

        foreach ([SIGINT, SIGTERM] as $signal) {
            pcntl_signal($signal, function () {
                $this->consumer?->stop();
            });
        }
    }

    private function getDefaultExchange(): string
    {
        $config = Config::get('commandbus.remote.transport');
        $default = $config['default'] ?? null;
        $connections = $config['connections'] ?? [];

        if (empty($default) || !isset($connections[$default])) {
            $default = array_key_first($connections);
        }

        return $connections[$default]['options']['exchange'];
    }
}
