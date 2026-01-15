<?php

declare(strict_types=1);

namespace Motomedialab\Connector\Tests\Stubs\Requests;

use Illuminate\Http\Client\Response;
use Motomedialab\Connector\BaseRequest;
use Motomedialab\Connector\Enums\RequestMethod;
use Motomedialab\Connector\Contracts\RequestInterface;

/**
 * @implements RequestInterface<array|false>
 */
readonly class PostRequest extends BaseRequest implements RequestInterface
{
    public function __construct(public array $inputData = [])
    {
        //
    }

    public function method(): RequestMethod
    {
        return RequestMethod::POST;
    }

    public function endpoint(): string
    {
        return 'postEndpoint';
    }

    public function body(): array
    {
        return $this->inputData;
    }

    public function toResponse(Response $response): array|false
    {
        return $response->ok() ? $response->json(default: []) : ['code' => $response->status(), ...$response->json()];
    }
}
