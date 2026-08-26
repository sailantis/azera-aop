<?php

declare(strict_types=1);

namespace Azera\Aop\Interceptor;

use Azera\Aop\Advice\Idempotent;
use Azera\Aop\InterceptorInterface;
use Psr\SimpleCache\CacheInterface;
use ReflectionMethod;

/**
 * Caches method results keyed by a request id, so repeated calls with
 * the same id return the cached result without re-invoking the method.
 *
 * The key's `{argName}` placeholders are substituted with the method's
 * arguments. Results are stored in a PSR-16 cache for the configured TTL.
 */
class IdempotentInterceptor implements InterceptorInterface
{
    public function __construct(
        private CacheInterface $cache,
    ) {}

    public function intercept(object $target, ReflectionMethod $method, array $args, callable $next): mixed
    {
        $advice = $this->advice($method);
        $key    = $this->resolveKey($advice->key, $args);

        $sentinel = new \stdClass();
        $cached   = $this->cache->get($key, $sentinel);

        if ($cached !== $sentinel) {
            return $cached;
        }

        $result = $next($args);
        $this->cache->set($key, $result, $advice->ttl);
        return $result;
    }

    private function advice(ReflectionMethod $method): Idempotent
    {
        $attrs = $method->getAttributes(Idempotent::class);
        return $attrs[0]->newInstance();
    }

    private function resolveKey(string $template, array $args): string
    {
        $replace = [];
        foreach ($args as $name => $value) {
            if (is_scalar($value) || $value === null) {
                $replace['{' . $name . '}'] = (string) $value;
            }
        }
        return strtr($template, $replace);
    }
}