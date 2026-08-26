<?php

declare(strict_types=1);

namespace Azera\Aop\Advice;

use Azera\Aop\Advice;

/**
 * Marks a method for argument validation before invocation.
 *
 * The {@see \Azera\Aop\Interceptor\ValidateInterceptor} invokes the
 * supplied validator (a callable or a class-string implementing
 * `__invoke(array $args): array<string,string>` returning errors) and
 * throws a `ValidationException` when errors are returned.
 *
 * Example:
 * <code>
 * #[Validate(validator: OrderValidator::class)]
 * public function createOrder(array $data): Order { ... }
 * </code>
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Validate extends Advice
{
    public function __construct(
        public readonly string $validator,
    ) {}
}