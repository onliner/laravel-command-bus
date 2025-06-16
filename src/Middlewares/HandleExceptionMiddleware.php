<?php

declare(strict_types=1);

namespace Onliner\Laravel\CommandBus\Middlewares;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Onliner\CommandBus\Context;
use Onliner\CommandBus\Middleware;
use Throwable;

class HandleExceptionMiddleware implements Middleware
{
    public function __construct(
        private ExceptionHandler $handler,
    ) {}

    public function call(object $message, Context $context, callable $next): void
    {
        try {
            $next($message, $context);
        } catch (Throwable $error) {
            $this->handler->report($error);

            throw $error;
        }
    }
}
