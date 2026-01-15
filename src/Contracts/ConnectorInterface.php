<?php

declare(strict_types=1);

namespace Motomedialab\Connector\Contracts;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\ConnectionException;

interface ConnectorInterface
{
    /**
     * @template TResponse
     *
     * @param  RequestInterface<TResponse>  $request
     * @return TResponse
     *
     * @throws ConnectionException
     */
    public function send(RequestInterface $request): mixed;

    public function authenticateRequest(PendingRequest $request): PendingRequest;

    public function apiUrl(): string;

    public function generateUrl(RequestInterface $request): string;
}
