<?php

declare(strict_types=1);

namespace Azera\Aop\Interceptor;

use Azera\Aop\Advice\Throttle;
use Azera\Aop\InterceptorInterface;
use Azera\Security\RateLimiter;
use ReflectionMethod;

/**
 * Rate-limits methods marked with {@see Throttle}.
 *
 * Uses the framework's {@see RateLimiter} (PSR-16-backed) to enforce
 * the configured budget. The key's `{argName}` placeholders are
 * substituted with the method's arguments, so limits can be scoped per
 * user/IP/tenant. Throws {@see ThrottleException} when the limit is
 * reached — the method is not invoked.
 */
class ThrottleInterceptor implements InterceptorInterface
{
    public function __construct(
        private RateLimiter $limiter,
    ) {}

    public function intercept(object $target, ReflectionMethod $method, array $args, callable $next): mixed
    {
        $advice = $this->advice($method);
        $key    = $this->resolveKey($advice->key, $args);

        if (!$this->limiter->limit($key, $advice->max, $advice->per)) {
            throw new ThrottleException(sprintf(
                'Rate limit exceeded for %s::%s (key: %s, max: %d per %ds)',
                $target::class,
                $method->getName(),
                $key,
                $advice->max,
                $advice->per,
            ));
        }

        return $next($args);
    }

    private function advice(ReflectionMethod $method): Throttle
    {
        $attrs = $method->getAttributes(Throttle::class);
        return $attrs[0]->newInstance();
    }

    /**
     * Substitute `{argName}` placeholders with method arguments.
     */
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