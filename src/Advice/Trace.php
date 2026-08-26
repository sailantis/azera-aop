<?php

declare(strict_types=1);

namespace Azera\Aop\Advice;

use Azera\Aop\Advice;

/**
 * Marks a method for span tracing.
 *
 * The {@see \Azera\Aop\Interceptor\TraceInterceptor} logs a span around
 * the method: start, end, duration, and any exception. Unlike the core
 * `#[Log]` advice (which logs the message itself), `Trace` is aimed at
 * span-style instrumentation — e.g. OpenTelemetry-compatible timing —
 * and emits a structured record per span.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Trace extends Advice
{
    public function __construct(
        public readonly ?string $name = null,
    ) {}
}