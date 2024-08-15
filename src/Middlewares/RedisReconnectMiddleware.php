<?php

declare(strict_types=1);

namespace Onliner\Laravel\CommandBus\Middlewares;

use Exception;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\RedisManager;
use Onliner\CommandBus\Context;
use Onliner\CommandBus\Middleware;

class RedisReconnectMiddleware implements Middleware
{
    public function __construct(
        private RedisManager $redis,
    ) {}

    public function call(object $message, Context $context, callable $next): void
    {
        $connections = $this->redis->connections() ?: [];

        foreach ($connections as $name => $connection) {
            if (!$this->ping($connection)) {
                $this->redis->purge($name);
            }
        }

        $next($message, $context);
    }

    private function ping(Connection $connection, string $msg = 'ok'): bool
    {
        try {
            return $connection->ping($msg) === $msg;
        } catch (Exception) {
            return false;
        }
    }
}
