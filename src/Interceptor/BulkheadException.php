<?php

declare(strict_types=1);

namespace Azera\Aop\Interceptor;

use RuntimeException;

/**
 * Thrown when a {@see \Azera\Aop\Advice\Bulkhead} limit is reached.
 */
class BulkheadException extends RuntimeException
{
}