<?php

declare(strict_types=1);

namespace Azera\Aop\Interceptor;

use Azera\Aop\Advice\Authorize;
use Azera\Aop\InterceptorInterface;
use ReflectionMethod;

/**
 * Enforces authorization via a `Gate` (from `azera-auth`).
 *
 * The interceptor is decoupled from `Azera\Auth\Authorization\Gate` by
 * duck-typing: any object exposing `authorize(string, mixed, ...$args)`
 * or `allows(string, mixed, ...$args)` works. This avoids a hard
 * dependency on `azera-auth` (the package is a `suggest`).
 *
 * The user is resolved from a callable supplied at construction (e.g.
 * `fn() => $ctx->get(AuthManagerInterface::class)->user()`).
 */
class AuthorizeInterceptor implements InterceptorInterface
{
    /**
     * @param object   $gate     A Gate-like object (authorize()/allows()).
     * @param callable $resolveUser fn(): mixed  resolves the current user.
     */
    public function __construct(
        private object $gate,
        private $resolveUser,
    ) {}

    public function intercept(object $target, ReflectionMethod $method, array $args, callable $next): mixed
    {
        $advice = $this->advice($method);
        $user   = ($this->resolveUser)();

        if ($advice->roles !== null) {
            foreach ($advice->roles as $role) {
                if (!$this->checkAbility('role:' . $role, $user, $args)) {
                    throw $this->denied($method, $role);
                }
            }
        }

        if ($advice->ability !== null) {
            if (!$this->checkAbility($advice->ability, $user, $args)) {
                throw $this->denied($method, $advice->ability);
            }
        }

        return $next($args);
    }

    private function checkAbility(string $ability, mixed $user, array $args): bool
    {
        if (method_exists($this->gate, 'allows')) {
            return (bool) $this->gate->allows($ability, $user, ...array_values($args));
        }
        if (method_exists($this->gate, 'authorize')) {
            try {
                $this->gate->authorize($ability, $user, ...array_values($args));
                return true;
            } catch (\Throwable) {
                return false;
            }
        }
        return false;
    }

    private function advice(ReflectionMethod $method): Authorize
    {
        $attrs = $method->getAttributes(Authorize::class);
        return $attrs[0]->newInstance();
    }

    private function denied(ReflectionMethod $method, string $ability): \Throwable
    {
        // Use the azera-auth exception when available, else a generic one.
        if (class_exists(\Azera\Auth\Authorization\AuthorizationException::class)) {
            return new \Azera\Auth\Authorization\AuthorizationException(
                sprintf('Authorization denied for %s (ability: %s)', $method->getName(), $ability),
            );
        }
        return new \RuntimeException(sprintf('Authorization denied for %s (ability: %s)', $method->getName(), $ability));
    }
}