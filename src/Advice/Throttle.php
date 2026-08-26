<?php

declare(strict_types=1);

namespace Azera\Aop\Advice;

use Azera\Aop\Advice;

/**
 * Marks a method for rate limiting.
 *
 * The {@see \Azera\Aop\Interceptor\ThrottleInterceptor} delegates to the
 * framework's {@see \Azera\Security\RateLimiter} (backed by a PSR-16
 * cache) and throws a {@see \Azera\Aop\Interceptor\ThrottleException}
 * when the limit is exceeded. The key supports `{argName}` placeholders
 * so limits can be per-user/per-IP.
 *
 * Example:
 * <code>
 * #[Throttle(key: 'api:{userId}', max: 100, per: 60)]
 * public function callApi(int $userId): Response { ... }
 * </code>
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Throttle extends Advice
{
    public function __construct(
        public readonly string $key,
        public readonly int $max,
        public readonly int $per,
    ) {}
}