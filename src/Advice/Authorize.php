<?php

declare(strict_types=1);

namespace Azera\Aop\Advice;

use Azera\Aop\Advice;

/**
 * Marks a method as requiring an authorization check.
 *
 * The {@see \Azera\Aop\Interceptor\AuthorizeInterceptor} delegates to
 * the `azera-auth` `Gate` to verify the authenticated user holds the
 * required role(s) or ability before invoking the method. On denial a
 * `AuthorizationException` is thrown (typically surfaced as HTTP 403).
 *
 * Example:
 * <code>
 * #[Authorize(ability: 'billing.manage')]
 * public function refund(int $invoiceId): void { ... }
 *
 * #[Authorize(roles: ['admin'])]
 * public function deleteUser(int $id): void { ... }
 * </code>
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Authorize extends Advice
{
    public function __construct(
        public readonly ?string $ability = null,
        public readonly ?array $roles = null,
    ) {}
}