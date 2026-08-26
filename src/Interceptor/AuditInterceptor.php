<?php

declare(strict_types=1);

namespace Azera\Aop\Interceptor;

use Azera\Aop\Advice\Audit;
use Azera\Aop\InterceptorInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ReflectionMethod;
use Throwable;

/**
 * Writes structured audit records for methods marked with {@see Audit}.
 *
 * Logs a record on success and on failure (the exception is re-thrown
 * after logging). The record includes the action, class, method,
 * selected args, duration, and result (on success).
 */
class AuditInterceptor implements InterceptorInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private string $level = LogLevel::INFO,
        private string $errorLevel = LogLevel::ERROR,
    ) {}

    public function intercept(object $target, ReflectionMethod $method, array $args, callable $next): mixed
    {
        $advice = $this->advice($method);
        $start  = microtime(true);

        try {
            $result = $next($args);
            $this->logger->log($this->level, $advice->action, [
                'class'       => $target::class,
                'method'      => $method->getName(),
                'args'        => $this->filterArgs($advice, $args),
                'duration_ms' => round((microtime(true) - $start) * 1000, 2),
                'result'      => $this->normalizeResult($result),
            ]);
            return $result;
        } catch (Throwable $e) {
            $this->logger->log($this->errorLevel, $advice->action . ' failed', [
                'class'       => $target::class,
                'method'      => $method->getName(),
                'args'        => $this->filterArgs($advice, $args),
                'duration_ms' => round((microtime(true) - $start) * 1000, 2),
                'error'       => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function advice(ReflectionMethod $method): Audit
    {
        $attrs = $method->getAttributes(Audit::class);
        return $attrs[0]->newInstance();
    }

    private function filterArgs(Audit $advice, array $args): array
    {
        if ($advice->logArgs === null) {
            return $args;
        }
        $filtered = [];
        foreach ($advice->logArgs as $name) {
            if (array_key_exists($name, $args)) {
                $filtered[$name] = $args[$name];
            }
        }
        return $filtered;
    }

    private function normalizeResult(mixed $result): mixed
    {
        if (is_scalar($result) || $result === null || is_array($result)) {
            return $result;
        }
        if ($result instanceof \UnitEnum) {
            return $result->name;
        }
        return $result::class;
    }
}