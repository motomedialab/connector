<?php

declare(strict_types=1);

namespace Motomedialab\Connector\Contracts;

use GuzzleHttp\Promise\PromiseInterface;
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

    /**
     * @template TResponse
     *
     * @param  RequestInterface<TResponse>  $request
     * @return TResponse
     *
     * @throws ConnectionException
     */
    public function sendAndRetry(RequestInterface $request, int $times = 3, int $sleepMs = 500, ?callable $when = null): mixed;

    /**
     * @template TResponse
     *
     * @param  RequestInterface<TResponse>  $request
     *
     * @throws ConnectionException
     */
    public function sendAsync(RequestInterface $request): PromiseInterface;

    /**
     * @template TResponse
     *
     * @param  RequestInterface<TResponse>  $request
     *
     * @throws ConnectionException
     */
    public function sendAndRetryAsync(RequestInterface $request, int $times = 3, int $sleepMs = 500, ?callable $when = null): PromiseInterface;

    public function authenticateRequest(PendingRequest $request): PendingRequest;

    public function apiUrl(): string;

    public function userAgent(): string;

    public function generateUrl(RequestInterface $request): string;
}
