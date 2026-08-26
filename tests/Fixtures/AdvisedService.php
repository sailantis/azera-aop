<?php

declare(strict_types=1);

namespace Azera\Aop\Tests\Fixtures;

use Azera\Aop\Advice\Audit;
use Azera\Aop\Advice\Bulkhead;
use Azera\Aop\Advice\CircuitBreaker;
use Azera\Aop\Advice\Idempotent;
use Azera\Aop\Advice\Throttle;
use Azera\Aop\Advice\Trace;
use Azera\Aop\Advice\Validate;

/**
 * Fixture service with advice attributes, so tests can obtain real
 * ReflectionMethod objects carrying the attributes for the interceptors.
 */
class AdvisedService
{
    public int $calls = 0;

    #[Audit(action: 'do_thing', logArgs: ['x'])]
    public function doThing(int $x): int
    {
        $this->calls++;
        return $x * 2;
    }

    #[Throttle(key: 'api:{userId}', max: 2, per: 60)]
    public function callApi(int $userId): string
    {
        return 'ok-' . $userId;
    }

    #[CircuitBreaker(failThreshold: 2, resetTimeout: 30)]
    public function flaky(bool $fail): string
    {
        if ($fail) {
            throw new \RuntimeException('boom');
        }
        return 'ok';
    }

    #[Idempotent(key: 'charge_{requestId}', ttl: 60)]
    public function charge(string $requestId, int $amount): array
    {
        $this->calls++;
        return ['charged', $amount];
    }

    #[Bulkhead(max: 2, ttl: 5)]
    public function heavy(): string
    {
        return 'done';
    }

    #[Trace(name: 'span.test')]
    public function traced(): string
    {
        return 'traced';
    }

    #[Validate(validator: self::class . '::validator')]
    public function createOrder(array $data): string
    {
        return 'order-' . ($data['name'] ?? 'unknown');
    }

    public static function validator(array $args): array
    {
        $errors = [];
        if (empty($args['data']['name'])) {
            $errors['name'] = 'Name is required.';
        }
        return $errors;
    }
}