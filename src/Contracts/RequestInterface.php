<?php

declare(strict_types=1);

namespace Motomedialab\Connector\Contracts;

use Illuminate\Http\Client\Response;
use Motomedialab\Connector\Enums\RequestMethod;

/**
 * @template TResponse
 */
interface RequestInterface
{
    public function method(): RequestMethod;

    public function endpoint(): string;

    public function queryParams(): array;

    public function body(): string|array;

    public function headers(): array;

    public function timeout(): int;

    public function authenticated(): bool;

    /**
     * @return TResponse
     */
    public function toResponse(Response $response): mixed;
}
