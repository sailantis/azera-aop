<?php

declare(strict_types=1);

namespace Azera\Aop\Interceptor;

use Azera\Aop\Advice\CircuitBreaker;
use Azera\Aop\InterceptorInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionMethod;
use Throwable;

/**
 * Circuit breaker interceptor backed by a PSR-16 cache.
 *
 * State machine per method (keyed by class::method):
 *  - **closed**: calls pass through; failures increment a counter.
 *    After `$failThreshold` failures, the circuit opens.
 *  - **open**: calls fail immediately with {@see CircuitBreakerOpenException}.
 *    After `$resetTimeout` seconds, the circuit enters half-open.
 *  - **half-open**: one trial call is permitted. On success the
 *    circuit closes (counters reset). On failure it re-opens.
 *
 * The counters and state are stored in the PSR-16 cache so the breaker
 * works across processes (PHP-FPM) when the cache is persistent.
 */
class CircuitBreakerInterceptor implements InterceptorInterface
{
    public function __construct(
        private CacheInterface $cache,
    ) {}

    public function intercept(object $target, ReflectionMethod $method, array $args, callable $next): mixed
    {
        $advice = $this->advice($method);
        $key    = $this->cacheKey($target::class, $method->getName());
        $state  = $this->readState($key);

        if ($state['state'] === 'open') {
            $elapsed = time() - $state['opened_at'];
            if ($elapsed >= $advice->resetTimeout) {
                // Half-open: allow one trial.
                $state['state'] = 'half-open';
                $this->writeState($key, $state);
            } else {
                throw new CircuitBreakerOpenException(sprintf(
                    'Circuit open for %s::%s (retry in %ds)',
                    $target::class,
                    $method->getName(),
                    $advice->resetTimeout - $elapsed,
                ));
            }
        }

        try {
            $result = $next($args);

            // Success resets the breaker.
            $this->writeState($key, ['state' => 'closed', 'failures' => 0, 'opened_at' => 0]);

            return $result;
        } catch (Throwable $e) {
            $state['failures']++;

            if ($state['state'] === 'half-open' || $state['failures'] >= $advice->failThreshold) {
                $state['state']     = 'open';
                $state['opened_at'] = time();
            }

            $this->writeState($key, $state);

            throw $e;
        }
    }

    private function advice(ReflectionMethod $method): CircuitBreaker
    {
        $attrs = $method->getAttributes(CircuitBreaker::class);
        return $attrs[0]->newInstance();
    }

    /** @return array{state: string, failures: int, opened_at: int} */
    private function readState(string $key): array
    {
        try {
            $state = $this->cache->get($key, null);
            if (is_array($state) && isset($state['state'])) {
                return $state;
            }
        } catch (InvalidArgumentException) {}
        return ['state' => 'closed', 'failures' => 0, 'opened_at' => 0];
    }

    private function writeState(string $key, array $state): void
    {
        try {
            $this->cache->set($key, $state);
        } catch (InvalidArgumentException) {}
    }

    private function cacheKey(string $class, string $method): string
    {
        return 'circuit.' . str_replace('\\', '.', $class) . '.' . $method;
    }
}