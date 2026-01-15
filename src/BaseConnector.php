<?php

declare(strict_types=1);

namespace Motomedialab\Connector;

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
        $response = $this->prepareRequest($request)->send(
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
        return $this->prepareRequest($request)
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

    protected function prepareRequest(RequestInterface $request): PendingRequest
    {
        return Http::withHeaders($request->headers())
            ->withHeader('User-Agent', 'MotoMediaLab/Connector')
            ->when($request->authenticated(), $this->authenticateRequest(...))
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
