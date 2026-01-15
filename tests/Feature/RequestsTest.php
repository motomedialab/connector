<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Motomedialab\Connector\Tests\Stubs;

beforeEach(function () {
    Http::preventStrayRequests();
});

describe('generic tests', function () {
    it('sends additional headers', function () {
        Http::fake(['*' => Http::response()]);

        $connector = new Stubs\Connectors\TestConnector();
        $connector->send(new Stubs\Requests\GetRequest(additionalHeaders: ['TestHeader' => 'TestValue']));

        Http::assertSent(fn (Request $request) => expect($request->header('TestHeader'))->toBe(['TestValue']));
    });

    it('sends authorization header', function () {
        Http::fake(['*' => Http::response()]);

        $connector = new Stubs\Connectors\TestConnector();
        $connector->send(new Stubs\Requests\GetRequest());

        Http::assertSent(fn (Request $request) => expect($request->header('AccessToken'))->toBe(['testing']));
    });
});

describe('get requests', function () {
    it('can send requests', function (array $params, string $url) {
        Http::fake([$url => Http::response()]);

        $connector = new Stubs\Connectors\TestConnector();
        $response = $connector->send(new Stubs\Requests\GetRequest($params));

        expect($response)->toBeTrue();

        Http::assertSent(function (Request $request) use ($url) {
            expect($request->method())->toBe('GET');
            expect($request->url())->toBe($url);
            return true;
        });
    })->with([
        'without query params' => [[], 'https://api.example.com/v2/getEndpoint'],
        'with query params' => [['test' => true], 'https://api.example.com/v2/getEndpoint?test=1'],
    ]);

    it('handles error responses', function () {
        Http::fake(['https://api.example.com/v2/getEndpoint' => Http::response([], 404)]);

        $connector = new Stubs\Connectors\TestConnector();
        $response = $connector->send(new Stubs\Requests\GetRequest());

        expect($response)->toBeFalse();
    });
});

describe('post requests', function () {
    it('handles successful requests', function () {
        Http::fake(['https://api.example.com/v2/postEndpoint' => Http::response(['success' => true])]);

        $connector = new Stubs\Connectors\TestConnector();
        $response = $connector->send(new Stubs\Requests\PostRequest(['test' => true]));

        expect($response)->toBe(['success' => true]);

        Http::assertSent(fn (Request $request) => expect($request)
            ->body()->toBe('{"test":true}')
            ->method()->toBe('POST'));
    });

    it('handles failed requests', function () {
        Http::fake(['https://api.example.com/v2/postEndpoint' => Http::response(['success' => false], 422)]);

        $connector = new Stubs\Connectors\TestConnector();
        $response = $connector->send(new Stubs\Requests\PostRequest(['test' => true]));

        expect($response)->toBe(['code' => 422, 'success' => false]);
    });
});
