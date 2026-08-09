---
name: motomedialab-connector
description: Best practices and code patterns for building and consuming API connectors and requests using motomedialab/connector in Laravel applications.
---

# MotoMediaLab Connector Guidelines

This skill provides patterns, conventions, and architectural best practices when creating Connectors and Requests using `motomedialab/connector` in Laravel applications.

## Key Principles

1. **Connector Responsibilities**: Connectors handle base configurations: `apiUrl()`, authentication (`authenticateRequest()`), default headers, and custom user agents (`userAgent()`).
2. **Request Responsibilities**: Requests encapsulate individual API endpoint contracts: endpoint path, HTTP method (`RequestMethod`), query parameters, body payload, timeout, and response transformation (`toResponse()`).
3. **Immutability & Typing**: Use `readonly` classes for requests, implement `RequestInterface<TResponse>`, and annotate generic return types for PHPStan/Larastan compatibility.
4. **Execution Flow**: Use `send()` for standard execution, `sendAndRetry()` / `sendAndRetryAsync()` for resilience, and `sendAsync()` for concurrent API calls.

---

## Defining a Connector

Connectors MUST extend `Motomedialab\Connector\BaseConnector`.

```php
namespace App\Connectors;

use Illuminate\Http\Client\PendingRequest;
use Motomedialab\Connector\BaseConnector;

class StripeConnector extends BaseConnector
{
    public function __construct(private readonly string $apiKey)
    {
    }

    public function apiUrl(): string
    {
        return 'https://api.stripe.com/v1/';
    }

    public function userAgent(): string
    {
        return 'MyApp-StripeConnector/1.0';
    }
}
```

### OAuth / Client Credentials Authentication Pattern

For APIs using OAuth2 Client Credentials flow, create an unauthenticated `ClientCredentialsRequest` and fetch/cache tokens inside `authenticateRequest()`:

```php
namespace App\Requests;

use Exception;
use Illuminate\Http\Client\Response;
use Motomedialab\Connector\BaseRequest;
use Motomedialab\Connector\Enums\RequestMethod;
use Motomedialab\Connector\Contracts\RequestInterface;

/**
 * @implements RequestInterface<array{token_type: string, access_token: string, expires_in: int}>
 */
readonly class ClientCredentialsRequest extends BaseRequest implements RequestInterface
{
    public function method(): RequestMethod
    {
        return RequestMethod::POST;
    }

    public function endpoint(): string
    {
        return 'oauth/token';
    }

    public function authenticated(): bool
    {
        return false; // Prevent recursive authentication loop
    }

    public function body(): array
    {
        return [
            'grant_type' => 'client_credentials',
            'client_id' => config('services.api.client_id'),
            'client_secret' => config('services.api.client_secret'),
            'scope' => config('services.api.scopes'),
        ];
    }

    public function toResponse(Response $response): array
    {
        if ($response->ok()) {
            return $response->json();
        }

        throw new Exception('Failed to fetch client_credentials token. Status code: ' . $response->status());
    }
}
```

Then consume `ClientCredentialsRequest` inside your connector with cache:

```php
namespace App\Connectors;

use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\PendingRequest;
use Motomedialab\Connector\BaseConnector;
use App\Requests\ClientCredentialsRequest;

class OAuthConnector extends BaseConnector
{
    public function apiUrl(): string
    {
        return 'https://api.example.com/v2/';
    }

    public function authenticateRequest(PendingRequest $request): PendingRequest
    {
        $token = Cache::remember('oauth_access_token', 3600, function () {
            $response = $this->send(new ClientCredentialsRequest());

            return $response['access_token'];
        });

        return $request->withToken($token);
    }
}
```

## Request Types & Formats

### Supported HTTP Verbs (`RequestMethod`)
- `RequestMethod::GET`: Resource retrieval (Default).
- `RequestMethod::POST`: Resource creation / action execution.
- `RequestMethod::PUT`: Full resource replacement.
- `RequestMethod::PATCH`: Partial resource updates.
- `RequestMethod::DELETE`: Resource deletion.

### Payload Formats (`Content-Type`)
`BaseConnector` dynamically structures `$request->body()` based on the `Content-Type` header:
- `application/json` (Default) -> `json` payload.
- `application/x-www-form-urlencoded` -> `form_params`.
- `multipart/form-data` -> `multipart`.
- `application/xml`, `text/plain` -> raw `body`.

---

## Defining Requests

Requests MUST extend `Motomedialab\Connector\BaseRequest` and SHOULD implement `Motomedialab\Connector\Contracts\RequestInterface`.

### 1. Minimal GET Request

```php
namespace App\Requests;

use Illuminate\Http\Client\Response;
use Motomedialab\Connector\BaseRequest;
use Motomedialab\Connector\Contracts\RequestInterface;

/**
 * @implements RequestInterface<array>
 */
readonly class GetCustomerRequest extends BaseRequest implements RequestInterface
{
    public function __construct(private string $customerId)
    {
    }

    public function endpoint(): string
    {
        return "customers/{$this->customerId}";
    }

    public function toResponse(Response $response): array
    {
        return $response->json();
    }
}
```

### 2. Full POST / Mutation Request

```php
namespace App\Requests;

use Illuminate\Http\Client\Response;
use Motomedialab\Connector\BaseRequest;
use Motomedialab\Connector\Contracts\RequestInterface;
use Motomedialab\Connector\Enums\RequestMethod;

/**
 * @implements RequestInterface<CustomerData>
 */
readonly class CreateCustomerRequest extends BaseRequest implements RequestInterface
{
    public function __construct(
        private string $email,
        private string $name,
    ) {
    }

    public function method(): RequestMethod
    {
        return RequestMethod::POST;
    }

    public function endpoint(): string
    {
        return 'customers';
    }

    public function body(): array
    {
        return [
            'email' => $this->email,
            'name' => $this->name,
        ];
    }

    public function timeout(): int
    {
        return 10;
    }

    public function toResponse(Response $response): CustomerData
    {
        $data = $response->throw()->json();

        return CustomerData::fromArray($data);
    }
}
```

---

## Executing Requests

### 1. Synchronous Dispatch

```php
$connector = new StripeConnector(config('services.stripe.secret'));
$customer = $connector->send(new GetCustomerRequest('cus_123'));
```

### 2. Retrying Requests

Use `sendAndRetry()` to handle transient network issues or rate limits.

```php
use Illuminate\Http\Client\ConnectionException;

// Retry up to 3 times with 500ms sleep between attempts
$customer = $connector->sendAndRetry(
    new GetCustomerRequest('cus_123'),
    times: 3,
    sleepMs: 500,
    when: fn (Throwable $e) => $e instanceof ConnectionException
);
```

### 3. Asynchronous & Concurrent Requests

Use `sendAsync()` or `sendAndRetryAsync()` alongside Guzzle promises for concurrent requests:

```php
use GuzzleHttp\Promise\Utils;

$requests = [
    new GetCustomerRequest('cus_1'),
    new GetCustomerRequest('cus_2'),
];

$promises = array_map(
    fn ($req) => $connector->sendAndRetryAsync($req, times: 3, sleepMs: 200),
    $requests
);

// Resolves all promises concurrently
$customers = Utils::unwrap($promises);
```

---

## Testing & Mocking

`motomedialab/connector` relies on Laravel's HTTP Client underneath, so use `Http::fake()` for testing:

```php
use Illuminate\Support\Facades\Http;
use App\Connectors\StripeConnector;
use App\Requests\GetCustomerRequest;

it('fetches customer details from stripe', function () {
    Http::fake([
        'https://api.stripe.com/v1/customers/cus_123' => Http::response([
            'id' => 'cus_123',
            'email' => 'user@example.com',
        ]),
    ]);

    $connector = new StripeConnector('sk_test_123');
    $response = $connector->send(new GetCustomerRequest('cus_123'));

    expect($response['email'])->toBe('user@example.com');
});
```
