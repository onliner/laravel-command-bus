<?php

declare(strict_types=1);

namespace Onliner\Laravel\CommandBus\Middlewares;

use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Redis\RedisManager;
use Onliner\CommandBus\Context;
use Onliner\CommandBus\Middleware;
use Predis\Connection\ConnectionException;

class RedisReconnectMiddleware implements Middleware
{
    public function __construct(
        private RedisManager $redis,
    ) {}

    public function call(object $message, Context $context, callable $next): void
    {
        $connections = $this->redis->connections() ?: [];

        foreach ($connections as $connection) {
            $this->ping($connection);
        }

        $next($message, $context);
    }

    private function ping(Connection $connection): void
    {
        if (!$connection instanceof PredisConnection) {
            return;
        }

        try {
            $connection->ping();
        } catch (ConnectionException) {
            $connection->disconnect();
            $connection->connect();
        }
    }
}
