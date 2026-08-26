<?php

declare(strict_types=1);

namespace Azera\Aop\Interceptor;

use RuntimeException;

/**
 * Thrown when {@see \Azera\Aop\Interceptor\ValidateInterceptor} finds
 * validation errors in the method's arguments.
 */
class ValidationException extends RuntimeException
{
    /** @var array<string, string> */
    private array $errors;

    /**
     * @param array<string, string> $errors
     */
    public function __construct(string $message, array $errors = [])
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }
}