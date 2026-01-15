<?php

declare(strict_types=1);

namespace Motomedialab\Connector\Tests\Stubs\Connectors;

use Motomedialab\Connector\BaseConnector;
use Illuminate\Http\Client\PendingRequest;

class TestConnector extends BaseConnector
{
    public function authenticateRequest(PendingRequest $request): PendingRequest
    {
        // test authenticate our requests
        return $request->withHeader('AccessToken', 'testing');
    }

    public function apiUrl(): string
    {
        return 'https://api.example.com/v2/';
    }
}
