<?php

declare(strict_types=1);

namespace Motomedialab\Connector\Tests\Stubs\Requests;

use Illuminate\Http\Client\Response;
use Motomedialab\Connector\BaseRequest;
use Motomedialab\Connector\Contracts\RequestInterface;

/**
 * @implements RequestInterface<bool>
 */
readonly class GetRequest extends BaseRequest implements RequestInterface
{
    public function __construct(public array $params = [], public array $additionalHeaders = [])
    {
        //
    }

    public function endpoint(): string
    {
        return 'getEndpoint';
    }

    public function headers(): array
    {
        return [
            ...parent::headers(),
            ...$this->additionalHeaders,
        ];
    }

    public function queryParams(): array
    {
        return $this->params;
    }

    public function toResponse(Response $response): bool
    {
        return $response->ok();
    }
}
