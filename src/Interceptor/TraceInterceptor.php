<?php

declare(strict_types=1);

namespace Azera\Aop\Interceptor;

use Azera\Aop\Advice\Trace;
use Azera\Aop\InterceptorInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ReflectionMethod;
use Throwable;

/**
 * Emits span-style timing records for methods marked with {@see Trace}.
 *
 * Logs a structured record on span start and end, with the duration in
 * ms. On exception, the span is marked as errored and the exception is
 * re-thrown. Designed for consumption by tracing instrumentation or
 * simple log-based profiling.
 */
class TraceInterceptor implements InterceptorInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private string $level = LogLevel::DEBUG,
        private string $errorLevel = LogLevel::ERROR,
    ) {}

    public function intercept(object $target, ReflectionMethod $method, array $args, callable $next): mixed
    {
        $advice = $this->advice($method);
        $name   = $advice->name ?? ($target::class . '::' . $method->getName());
        $start  = microtime(true);

        $this->logger->log($this->level, 'span.start', ['span' => $name]);

        try {
            $result = $next($args);
            $this->logger->log($this->level, 'span.end', [
                'span'        => $name,
                'duration_ms' => round((microtime(true) - $start) * 1000, 2),
                'errored'     => false,
            ]);
            return $result;
        } catch (Throwable $e) {
            $this->logger->log($this->errorLevel, 'span.end', [
                'span'        => $name,
                'duration_ms' => round((microtime(true) - $start) * 1000, 2),
                'errored'     => true,
                'error'       => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function advice(ReflectionMethod $method): Trace
    {
        $attrs = $method->getAttributes(Trace::class);
        return $attrs[0]->newInstance();
    }
}