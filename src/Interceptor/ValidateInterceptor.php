<?php

declare(strict_types=1);

namespace Azera\Aop\Interceptor;

use Azera\Aop\Advice\Validate;
use Azera\Aop\InterceptorInterface;
use ReflectionMethod;
use RuntimeException;

/**
 * Validates method arguments before invocation.
 *
 * The validator is resolved from the attribute's `validator` string:
 * a class name implementing `__invoke(array $args): array` (returning
 * a map of field => error message, empty = valid), or a callable. The
 * interceptor throws a {@see ValidationException} when the validator
 * returns any errors.
 */
class ValidateInterceptor implements InterceptorInterface
{
    /** @var array<string, callable> */
    private array $resolved = [];

    public function intercept(object $target, ReflectionMethod $method, array $args, callable $next): mixed
    {
        $advice    = $this->advice($method);
        $validator = $this->resolve($advice->validator);

        $errors = $validator($args);

        if (!empty($errors)) {
            throw new ValidationException(
                sprintf('Validation failed for %s::%s', $target::class, $method->getName()),
                is_array($errors) ? $errors : [],
            );
        }

        return $next($args);
    }

    private function advice(ReflectionMethod $method): Validate
    {
        $attrs = $method->getAttributes(Validate::class);
        return $attrs[0]->newInstance();
    }

    private function resolve(string $validator): callable
    {
        if (isset($this->resolved[$validator])) {
            return $this->resolved[$validator];
        }

        if (is_callable($validator)) {
            $resolved = $validator(...);
        } elseif (class_exists($validator)) {
            $instance = new $validator();
            $resolved = $instance(...);
        } else {
            throw new RuntimeException(sprintf('Validator "%s" is not callable.', $validator));
        }

        return $this->resolved[$validator] = $resolved;
    }
}