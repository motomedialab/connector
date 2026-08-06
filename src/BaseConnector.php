<?php

declare(strict_types=1);

namespace Motomedialab\Connector;

use Throwable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\ConnectionException;
use Motomedialab\Connector\Contracts\RequestInterface;
use Motomedialab\Connector\Contracts\ConnectorInterface;

abstract class BaseConnector implements ConnectorInterface
{
    /**
     * @template TResponse
     *
     * @param  RequestInterface<TResponse>  $request
     * @return TResponse
     *
     * @throws ConnectionException
     */
    public function send(RequestInterface $request): mixed
    {
        return $this->sendAndRetry($request, 1);
    }

    /**
     * @template TResponse
     *
     * @param  RequestInterface<TResponse>  $request
     * @param  int  $times  The number of times the request should be retried
     * @param  int  $sleepMs  The number of milliseconds to wait between requests
     * @param  (callable(Throwable, PendingRequest): bool)|null  $when  The callback that will determine if the request should be retried.
     * @return TResponse
     *
     * @throws ConnectionException
     */
    public function sendAndRetry(RequestInterface $request, int $times = 3, int $sleepMs = 500, ?callable $when = null): mixed
    {
        $response = $this->prepareRequest($request, $times, $sleepMs, $when)->send(
            $request->method()->value,
            $this->generateUrl($request),
            $this->prepareBody($request),
        );

        return $request->toResponse($response);
    }

    /**
     * @template TResponse
     *
     * @param  RequestInterface<TResponse>  $request
     *
     * @throws ConnectionException
     */
    public function sendAsync(RequestInterface $request): PromiseInterface
    {
        return $this->sendAndRetryAsync($request, 1);
    }

    /**
     * @template TResponse
     *
     * @param  RequestInterface<TResponse>  $request
     * @param  int  $times  The number of times the request should be retried
     * @param  int  $sleepMs  The number of milliseconds to wait between requests
     * @param  (callable(Throwable, PendingRequest): bool)|null  $when  The callback that will determine if the request should be retried.
     *
     * @throws ConnectionException
     */
    public function sendAndRetryAsync(RequestInterface $request, int $times = 3, int $sleepMs = 500, ?callable $when = null): PromiseInterface
    {
        return $this->prepareRequest($request, $times, $sleepMs, $when)
            ->async()
            ->send(
                $request->method()->value,
                $this->generateUrl($request),
                $this->prepareBody($request)
            )
            ->then(fn (Response $response) => $request->toResponse($response));
    }

    public function generateUrl(RequestInterface $request): string
    {
        $query = http_build_query($request->queryParams());
        $url = $this->apiUrl().$request->endpoint();

        return $query ? $url.'?'.$query : $url;
    }

    public function userAgent(): string
    {
        return 'Motomedialab/Connector';
    }

    protected function prepareRequest(RequestInterface $request, int $times = 1, int $sleepMs = 0, ?callable $when = null): PendingRequest
    {
        return Http::withHeaders($request->headers())
            ->withHeader('User-Agent', $this->userAgent())
            ->when($request->authenticated(), $this->authenticateRequest(...))
            ->when($times > 1, fn (PendingRequest $pending) => $pending->retry($times, $sleepMs, $when))
            ->timeout($request->timeout());
    }

    protected function prepareBody(RequestInterface $request): array
    {
        $contentType = collect($request->headers())->first(fn ($value, $key) => strtolower($key) === 'content-type') ?? 'application/json';

        $bodyType = match (true) {
            str_contains($contentType, 'form') => 'form_params',
            str_contains($contentType, 'multipart') => 'multipart',
            str_contains($contentType, 'xml') => 'xml',
            str_contains($contentType, 'plain') => 'body',
            default => 'json',
        };

        return array_filter([$bodyType => $request->body()]);
    }
}
