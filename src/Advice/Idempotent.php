<?php

declare(strict_types=1);

namespace Azera\Aop\Advice;

use Azera\Aop\Advice;

/**
 * Marks a method as idempotent.
 *
 * The {@see \Azera\Aop\Interceptor\IdempotentInterceptor} stores the
 * method's result keyed by a request identifier (from an argument,
 * typically). Repeated calls with the same id return the cached result
 * without re-invoking the method. Useful for payment endpoints and
 * other operations that must not be executed twice for the same
 * request.
 *
 * Example:
 * <code>
 * #[Idempotent(key: 'charge_{requestId}', ttl: 3600)]
 * public function chargeCard(string $requestId, int $amount): array { ... }
 * </code>
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Idempotent extends Advice
{
    public function __construct(
        public readonly string $key,
        public readonly int $ttl = 3600,
    ) {}
}