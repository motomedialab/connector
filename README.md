# MotoMediaLab Connector

[![MIT License](https://img.shields.io/badge/License-MIT-green.svg)](https://choosealicense.com/licenses/mit/)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/motomedialab/connector.svg?style=flat-square)](https://packagist.org/packages/motomedialab/connector)
[![Total Downloads](https://img.shields.io/packagist/dt/motomedialab/connector.svg?style=flat-square)](https://packagist.org/packages/motomedialab/connector)
[![Tests](https://github.com/motomedialab/connector/actions/workflows/tests.yml/badge.svg)](https://github.com/motomedialab/connector/actions/workflows/tests.yml)

A super lightweight, opinionated connector pattern for Laravel to make light work of consuming third-party APIs.

## Introduction

Integrating with external APIs often involves repetitive boilerplate for handling authentication, endpoints, 
and requests. This package provides a structured and reusable pattern to streamline this process, keeping your 
code clean, consistent, and easy to maintain.

## Installation

You can install the package via composer:

```bash
composer require motomedialab/connector
```

## Core Concepts

The package is built around two core concepts: [Connectors](#connectors) and [Requests](#requests).

### Connectors

The `Connector` is responsible for defining the base URL and authentication method for an API. Your connector **must** extend the `Motomedialab\Connector\BaseConnector` abstract class.

```php
// app/Connectors/ExampleConnector.php
use Illuminate\Http\Client\PendingRequest;
use Motomedialab\Connector\BaseConnector;

class ExampleConnector extends BaseConnector
{
    /**
     * Optionally authenticate all requests from this connector.
     * For example, an access or bearer token. 
     */
    public function authenticateRequest(PendingRequest $request): PendingRequest
    {
        return $request->withToken('your-secret-token');
    }

    /**
     * Define the base URL for the API you are connecting with
     */
    public function apiUrl(): string
    {
        return 'https://api.example.com/v2/';
    }
}
```

#### OAuth / Client Credentials Authentication

When authenticating via OAuth (e.g., Client Credentials flow), create an unauthenticated `ClientCredentialsRequest` and resolve access tokens dynamically (with caching) inside `authenticateRequest()`:

```php
// app/Requests/ClientCredentialsRequest.php
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

    // Mark as unauthenticated so the connector doesn't loop authentication
    public function authenticated(): bool
    {
        return false;
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

Then consume `ClientCredentialsRequest` within your `Connector`:

```php
// app/Connectors/ExampleConnector.php
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\PendingRequest;
use Motomedialab\Connector\BaseConnector;
use App\Requests\ClientCredentialsRequest;

class ExampleConnector extends BaseConnector
{
    public function apiUrl(): string
    {
        return 'https://api.example.com/v2/';
    }

    public function authenticateRequest(PendingRequest $request): PendingRequest
    {
        $token = Cache::remember('api_oauth_token', 3600, function () {
            $data = $this->send(new ClientCredentialsRequest());

            return $data['access_token'];
        });

        return $request->withToken($token);
    }
}
```

### Requests

The `Request` defines the specific details of an API call, such as the endpoint, method, headers, 
and payload. Your request classes **must** extend the `Motomedialab\Connector\BaseRequest` abstract class 
and **should** implement the `Motomedialab\Connector\Contracts\RequestInterface` contract.

The `BaseRequest` provides sensible defaults, so you only need to define what you need to override.

#### Simple GET Request

Here's a minimal example for a simple GET request:

```php
// app/Requests/ExampleGetRequest.php
use Illuminate\Http\Client\Response;
use Motomedialab\Connector\BaseRequest;
use Motomedialab\Connector\Contracts\RequestInterface;

/**
 * @implements RequestInterface<array>
 */
readonly class ExampleGetRequest extends BaseRequest implements RequestInterface
{
    public function __construct(private string $id)
    {
       //
    }

    public function endpoint(): string
    {
        return "users/{$this->id}";
    }
    
    public function toResponse(Response $response): array
    {
        return $response->json();
    }
}
```

#### Advanced POST Request

This example demonstrates all available methods for customising a request.

```php
// app/Requests/ExamplePostRequest.php
use Illuminate\Http\Client\Response;
use Motomedialab\Connector\BaseRequest;
use Motomedialab\Connector\Enums\RequestMethod;
use Motomedialab\Connector\Contracts\RequestInterface;

/**
 * @implements RequestInterface<array>
 */
readonly class ExamplePostRequest extends BaseRequest implements RequestInterface
{
    public function __construct(public array $inputData = [])
    {
      //
    }

    // Define the HTTP method. Defaults to GET.
    public function method(): RequestMethod
    {
        return RequestMethod::POST;
    }
    
    // Specify the request timeout in seconds. Defaults to 5.
    public function timeout(): int
    {
        return 10;
    }
    
    // Add query parameters to the URL.
    public function queryParams(): array
    {
        return ['include' => 'posts'];
    }
    
    // Add or override headers. Defaults include JSON content type.
    public function headers(): array
    {
        return [
            ...parent::headers(),
            'X-Custom-Header' => 'CustomValue',
        ];
    }

    // REQUIRED: Define the endpoint, appended to the connector's apiUrl.
    public function endpoint(): string
    {
        return 'users';
    }

    // Define the request payload.
    public function body(): array
    {
        return $this->inputData;
    }
    
    // Determine if the request requires authentication. Defaults to true.
    public function authenticated(): bool
    {
        return true;
    }

    // Transform the successful response.
    public function toResponse(Response $response): array
    {
        if ($response->failed()) {
            // You can handle error responses here.
            // For example, return a default structure or throw an exception.
            return ['error' => true, 'status' => $response->status()];
        }
        
        return $response->json();
    }
}
```

#### HTTP Methods & Payload Formats

1. **HTTP Methods (`RequestMethod`)**: Set the HTTP verb via `method()`. Defaults to `GET`.
   - `RequestMethod::GET`
   - `RequestMethod::POST`
   - `RequestMethod::PUT`
   - `RequestMethod::PATCH`
   - `RequestMethod::DELETE`

2. **Body Payload Formats**: The connector automatically maps `$this->body()` based on the request's `Content-Type` header:
   - **JSON (`application/json`)**: Default behavior.
   - **Form (`application/x-www-form-urlencoded`)**: Form URL-encoded data.
   - **Multipart (`multipart/form-data`)**: File uploads and form data.
   - **XML / Plain (`application/xml`, `text/plain`)**: Raw body payload.

## Usage

### Sending a Request

To send a request, simply instantiate your connector and request, then call the `send()` method.

```php
$connector = new ExampleConnector();
$request = new ExamplePostRequest(['name' => 'Chris']);

// The response will be whatever you return from toResponse()
$response = $connector->send($request);
```

### Retrying Requests

You can automatically retry failed requests using `sendAndRetry()`:

```php
// Retry up to 3 times with a 500ms delay between attempts
$response = $connector->sendAndRetry($request, times: 3, sleepMs: 500);

// You can also pass an optional $when callback to control when retries should occur
$response = $connector->sendAndRetry(
    $request,
    times: 3,
    sleepMs: 500,
    when: fn (Throwable $exception) => $exception instanceof ConnectionException
);
```

### Asynchronous Requests

You can also send requests concurrently using `sendAsync()` or `sendAndRetryAsync()`. This is great for
performance when you need to make multiple independent API calls.

```php
use GuzzleHttp\Promise\Utils;

$connector = new ExampleConnector();

// Create an array of requests
$requests = [
    new ExampleGetRequest('user-1'),
    new ExampleGetRequest('user-2'),
    new ExampleGetRequest('user-3'),
];

// Map requests to promises (with optional retry support)
$promises = array_map(
    fn($request) => $connector->sendAndRetryAsync($request, times: 3, sleepMs: 500),
    $requests
);

// Wait for all promises to resolve
$responses = Utils::unwrap($promises);
```

## Testing

This package is designed to work seamlessly with Laravel's `Http::fake()`. You can write your
tests as you normally would.

```php
use Illuminate\Support\Facades\Http;
use App\Connectors\ExampleConnector;
use App\Requests\ExampleGetRequest;

it('sends a get request', function () {
    Http::fake([
        'api.example.com/*' => Http::response(['name' => 'John']),
    ]);

    $connector = new ExampleConnector();
    $response = $connector->send(new ExampleGetRequest('user-1'));
    
    expect($response['name'])->toBe('John');
});
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.