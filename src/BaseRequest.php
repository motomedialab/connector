<?php

declare(strict_types=1);

namespace Motomedialab\Connector;

use Motomedialab\Connector\Enums\RequestMethod;
use Motomedialab\Connector\Contracts\RequestInterface;

/**
 * @implements RequestInterface<mixed>
 */
abstract readonly class BaseRequest implements RequestInterface
{
    public function method(): RequestMethod
    {
        return RequestMethod::GET;
    }

    /**
     * @return string|array
     */
    public function body(): string|array
    {
        return [];
    }

    public function timeout(): int
    {
        return 5;
    }

    /**
     * @return array
     */
    public function queryParams(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public function headers(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    public function authenticated(): bool
    {
        return true;
    }
}
