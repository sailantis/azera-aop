<?php

declare(strict_types=1);

namespace Azera\Aop\Interceptor;

use RuntimeException;

/**
 * Thrown when a {@see \Azera\Aop\Advice\Throttle} limit is exceeded.
 */
class ThrottleException extends RuntimeException
{
}