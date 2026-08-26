<?php

declare(strict_types=1);

namespace Azera\Aop\Advice;

use Azera\Aop\Advice;

/**
 * Marks a method as protected by a circuit breaker.
 *
 * The {@see \Azera\Aop\Interceptor\CircuitBreakerInterceptor} tracks
 * failures (via PSR-16 cache) and opens the circuit after
 * `$failThreshold` failures, fast-failing subsequent calls for
 * `$resetTimeout` seconds. After the timeout, the circuit enters
 * half-open: a single trial call is permitted; on success the circuit
 * closes, on failure it re-opens.
 *
 * Example:
 * <code>
 * #[CircuitBreaker(failThreshold: 5, resetTimeout: 30)]
 * public function callExternalApi(): Response { ... }
 * </code>
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class CircuitBreaker extends Advice
{
    public function __construct(
        public readonly int $failThreshold = 5,
        public readonly int $resetTimeout = 30,
    ) {}
}