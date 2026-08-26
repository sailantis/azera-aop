<?php

declare(strict_types=1);

namespace Azera\Aop\Interceptor;

use Azera\Aop\Advice\Bulkhead;
use Azera\Aop\InterceptorInterface;
use Psr\SimpleCache\CacheInterface;
use ReflectionMethod;

/**
 * Limits concurrent invocations via a counting semaphore in PSR-16.
 *
 * Each call increments a counter (with TTL = `$ttl` to prevent leaks
 * if a process dies mid-call); after the call completes (success or
 * failure) the counter is decremented. When the counter is at `$max`,
 * new calls throw {@see BulkheadException} immediately.
 *
 * Note: this is best-effort for single-process or low-concurrency
 * scenarios. For true distributed bulkheads, use an atomic increment
 * (Redis `INCR`).
 */
class BulkheadInterceptor implements InterceptorInterface
{
    public function __construct(
        private CacheInterface $cache,
    ) {}

    public function intercept(object $target, ReflectionMethod $method, array $args, callable $next): mixed
    {
        $advice = $this->advice($method);
        $key    = $this->cacheKey($target::class, $method->getName());

        $current = $this->cache->get($key, 0);
        $current = is_int($current) ? $current : 0;

        if ($current >= $advice->max) {
            throw new BulkheadException(sprintf(
                'Concurrency limit reached for %s::%s (max: %d)',
                $target::class,
                $method->getName(),
                $advice->max,
            ));
        }

        $this->cache->set($key, $current + 1, $advice->ttl);

        try {
            return $next($args);
        } finally {
            $after = $this->cache->get($key, 1);
            $after = is_int($after) ? $after : 1;
            $this->cache->set($key, max(0, $after - 1), $advice->ttl);
        }
    }

    private function advice(ReflectionMethod $method): Bulkhead
    {
        $attrs = $method->getAttributes(Bulkhead::class);
        return $attrs[0]->newInstance();
    }

    private function cacheKey(string $class, string $method): string
    {
        return 'bulkhead.' . str_replace('\\', '.', $class) . '.' . $method;
    }
}