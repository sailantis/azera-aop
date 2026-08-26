<?php

declare(strict_types=1);

namespace Azera\Aop\Advice;

use Azera\Aop\Advice;

/**
 * Marks a method for audit logging.
 *
 * The {@see \Azera\Aop\Interceptor\AuditInterceptor} writes a structured
 * audit record (action, class, method, args, duration, result) to a
 * PSR-3 logger after the method completes. On exception, the failure
 * is logged but re-thrown.
 *
 * Example:
 * <code>
 * #[Audit(action: 'user.delete', logArgs: ['id'])]
 * public function deleteUser(int $id): void { ... }
 * </code>
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Audit extends Advice
{
    /**
     * @param string       $action   Human-readable action name for the audit record.
     * @param string[]|null $logArgs  Argument names to include in the record (null = all, [] = none).
     */
    public function __construct(
        public readonly string $action,
        public readonly ?array $logArgs = null,
    ) {}
}