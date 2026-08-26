<?php

declare(strict_types=1);

namespace Azera\Aop\Tests;

use Azera\Aop\Interceptor\AuditInterceptor;
use Azera\Aop\Interceptor\CircuitBreakerInterceptor;
use Azera\Aop\Interceptor\CircuitBreakerOpenException;
use Azera\Aop\Interceptor\IdempotentInterceptor;
use Azera\Aop\Interceptor\ThrottleException;
use Azera\Aop\Interceptor\ThrottleInterceptor;
use Azera\Aop\Interceptor\TraceInterceptor;
use Azera\Cache\ArrayCache;
use Azera\Security\RateLimiter;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use ReflectionMethod;

final class InterceptorsTest extends TestCase
{
    private function method(string $class, string $method): ReflectionMethod
    {
        return new ReflectionMethod($class, $method);
    }

    public function test_audit_logs_on_success(): void
    {
        $logger = new class extends AbstractLogger
        {
            public array $records = [];
            public function log($level, $message, array $context = []): void
            {
                $this->records[] = compact('level', 'message', 'context');
            }
        };

        $service     = new Fixtures\AdvisedService();
        $method      = $this->method($service::class, 'doThing');
        $interceptor = new AuditInterceptor($logger);

        $result = $interceptor->intercept($service, $method, ['x' => 21], fn(array $a) => $service->doThing(...$a));

        self::assertSame(42, $result);
        self::assertCount(1, $logger->records);
        self::assertSame('do_thing', $logger->records[0]['message']);
        self::assertSame(['x' => 21], $logger->records[0]['context']['args']);
    }

    public function test_throttle_allows_then_denies(): void
    {
        $limiter     = new RateLimiter(new ArrayCache());
        $interceptor = new ThrottleInterceptor($limiter);
        $service     = new Fixtures\AdvisedService();
        $method      = $this->method($service::class, 'callApi');

        // First two calls succeed (max: 2).
        $r1 = $interceptor->intercept($service, $method, ['userId' => 5], fn() => 'ok-5');
        $r2 = $interceptor->intercept($service, $method, ['userId' => 5], fn() => 'ok-5');
        self::assertSame('ok-5', $r1);
        self::assertSame('ok-5', $r2);

        // Third call exceeds the limit.
        $this->expectException(ThrottleException::class);
        $interceptor->intercept($service, $method, ['userId' => 5], fn() => 'ok-5');
    }

    public function test_circuit_breaker_opens_after_failures(): void
    {
        $cache       = new ArrayCache();
        $interceptor = new CircuitBreakerInterceptor($cache);
        $service     = new Fixtures\AdvisedService();
        $method      = $this->method($service::class, 'flaky');

        // Two failures open the circuit.
        try {
            $interceptor->intercept($service, $method, ['fail' => true], fn() => $service->flaky(true));
        } catch (\Throwable) {}
        try {
            $interceptor->intercept($service, $method, ['fail' => true], fn() => $service->flaky(true));
        } catch (\Throwable) {}

        // Third call is short-circuited (circuit open).
        $this->expectException(CircuitBreakerOpenException::class);
        $interceptor->intercept($service, $method, ['fail' => false], fn() => $service->flaky(false));
    }

    public function test_circuit_breaker_resets_on_success(): void
    {
        $cache       = new ArrayCache();
        $interceptor = new CircuitBreakerInterceptor($cache);
        $service     = new Fixtures\AdvisedService();
        $method      = $this->method($service::class, 'flaky');

        // One failure (below threshold).
        try {
            $interceptor->intercept($service, $method, ['fail' => true], fn() => $service->flaky(true));
        } catch (\Throwable) {}

        // Success resets the breaker.
        $result = $interceptor->intercept($service, $method, ['fail' => false], fn() => $service->flaky(false));
        self::assertSame('ok', $result);

        // State should be closed with 0 failures.
        $state = $cache->get('circuit.Azera.Aop.Tests.Fixtures.AdvisedService.flaky');
        self::assertSame('closed', $state['state']);
        self::assertSame(0, $state['failures']);
    }

    public function test_idempotent_caches_result(): void
    {
        $cache       = new ArrayCache();
        $interceptor = new IdempotentInterceptor($cache);
        $service     = new Fixtures\AdvisedService();
        $method      = $this->method($service::class, 'charge');

        $r1 = $interceptor->intercept($service, $method, ['requestId' => 'r1', 'amount' => 100], fn() => $service->charge(...['requestId' => 'r1', 'amount' => 100]));
        $r2 = $interceptor->intercept($service, $method, ['requestId' => 'r1', 'amount' => 100], fn() => $service->charge(...['requestId' => 'r1', 'amount' => 100]));

        // The method was only invoked once.
        self::assertSame(1, $service->calls);
        self::assertSame($r1, $r2);
    }

    public function test_trace_logs_span_start_and_end(): void
    {
        $logger = new class extends AbstractLogger
        {
            public array $records = [];
            public function log($level, $message, array $context = []): void
            {
                $this->records[] = compact('level', 'message', 'context');
            }
        };

        $interceptor = new TraceInterceptor($logger);
        $service     = new Fixtures\AdvisedService();
        $method      = $this->method($service::class, 'traced');

        $interceptor->intercept($service, $method, [], fn() => $service->traced());

        self::assertCount(2, $logger->records);
        self::assertSame('span.start', $logger->records[0]['message']);
        self::assertSame('span.end', $logger->records[1]['message']);
        self::assertSame('span.test', $logger->records[1]['context']['span']);
        self::assertFalse($logger->records[1]['context']['errored']);
    }
}