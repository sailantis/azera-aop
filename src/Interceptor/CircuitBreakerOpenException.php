<?php

declare(strict_types=1);

namespace Azera\Aop\Interceptor;

use RuntimeException;

/**
 * Thrown when a {@see \Azera\Aop\Advice\CircuitBreaker} is open and
 * short-circuits a call.
 */
class CircuitBreakerOpenException extends RuntimeException
{
}