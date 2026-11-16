# Changelog

All notable changes to `sailantis/azera-aop` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.4.2] - 2026-11-16

First tagged release. Version numbers follow `sailantis/azera-framework`, because
these interceptors extend the framework's AOP engine and may need to track its
minor releases.

### Added

- `Azera\Aop\Advice\Audit` + `AuditInterceptor` — structured audit records to a PSR-3 logger.
- `Azera\Aop\Advice\Throttle` + `ThrottleInterceptor` — method-level rate limiting via `RateLimiter` and PSR-16.
- `Azera\Aop\Advice\Authorize` + `AuthorizeInterceptor` — role/ability checks via `azera-auth`'s `Gate`.
- `Azera\Aop\Advice\Validate` + `ValidateInterceptor` — runs a validation rule set against method arguments.
- `Azera\Aop\Advice\CircuitBreaker` + `CircuitBreakerInterceptor` — open/half-open/closed state via PSR-16.
- `Azera\Aop\Advice\Idempotent` + `IdempotentInterceptor` — request-id → result caching.
- `Azera\Aop\Advice\Bulkhead` + `BulkheadInterceptor` — concurrency limit via a counting cache key.
- `Azera\Aop\Advice\Trace` + `TraceInterceptor` — span start/end/duration around the method.
