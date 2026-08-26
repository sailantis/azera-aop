<?php

declare(strict_types=1);

namespace Azera\Aop\Advice;

use Azera\Aop\Advice;

/**
 * Limits concurrent invocations of a method.
 *
 * The {@see \Azera\Aop\Interceptor\BulkheadInterceptor} uses a counting
 * semaphore (backed by PSR-16 cache) to cap the number of concurrent
 * calls to `$max`. When the cap is reached, a
 * {@see \Azera\Aop\Interceptor\BulkheadException} is thrown.
 *
 * Example:
 * <code>
 * #[Bulkhead(max: 10, ttl: 30)]
 * public function heavyJob(): void { ... }
 * </code>
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Bulkhead extends Advice
{
    public function __construct(
        public readonly int $max,
        public readonly int $ttl = 30,
    ) {}
}