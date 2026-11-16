# azera-aop

AOP companion for the [Azera framework](../azera-framework).

The framework ships the AOP engine (`ProxyFactory`, `Pipeline`, `Advice`, `InterceptorInterface`, `Advised`) plus 4 built-in advices (`Transactional`, `Cache`, `Retry`, `Log`). This package provides the long-tail cross-cutting concerns that don't belong in core:

- `Azera\Aop\Advice\Audit` + `AuditInterceptor` — structured audit records to a PSR-3 logger.
- `Azera\Aop\Advice\Throttle` + `ThrottleInterceptor` — method-level rate limiting via `RateLimiter` + PSR-16.
- `Azera\Aop\Advice\Authorize` + `AuthorizeInterceptor` — role/ability checks via `azera-auth`'s `Gate`.
- `Azera\Aop\Advice\Validate` + `ValidateInterceptor` — run a validation rule set against method args.
- `Azera\Aop\Advice\CircuitBreaker` + `CircuitBreakerInterceptor` — open/half-open/closed via PSR-16.
- `Azera\Aop\Advice\Idempotent` + `IdempotentInterceptor` — request-id → result caching (payments).
- `Azera\Aop\Advice\Bulkhead` + `BulkheadInterceptor` — concurrency limit via counting cache key.
- `Azera\Aop\Advice\Trace` + `TraceInterceptor` — span start/end/duration around the method.

## Installation

```bash
composer require sailantis/azera-aop
```

Optional companions, needed only by the interceptors that delegate to them:

| Interceptor | Requires |
|---|---|
| `AuthorizeInterceptor` | `sailantis/azera-auth` (provides `Gate`) |
| `ThrottleInterceptor`, `IdempotentInterceptor`, `BulkheadInterceptor`, `CircuitBreakerInterceptor` | a PSR-16 cache; `sailantis/azera-cache` provides the backends |

Each is an independent attribute + interceptor pair. Register with `AppContext::registerInterceptor($adviceClass, $interceptor)` and mark services `#[Advised]`.

## License

MIT.