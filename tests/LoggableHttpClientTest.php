<?php

namespace Liborm85\LoggableHttpClient\Tests;

use Liborm85\LoggableHttpClient\LoggableHttpClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class LoggableHttpClientTest extends TestCase
{

    public function testJsonContentType(): void
    {
        $mock = new MockHttpClient(function (string $method, string $url, array $options) {
            $this->assertSame(['Content-Type: application/json'], $options['normalized_headers']['content-type']);
            $this->assertSame('{"name":"Libor"}', $options['body']);

            return new MockResponse('OK');
        });

        $client = new LoggableHttpClient($mock);
        $response = $client->request('POST', 'https://example.com', ['json' => ['name' => 'Libor']]);

        $this->assertSame('OK', $response->getContent());
    }

    public function testJsonPreservesCustomContentType(): void
    {
        foreach ([['cOnTeNt-TyPe' => 'application/vnd.example+json'], ['cOnTeNt-TyPe: application/vnd.example+json']] as $headers) {
            $mock = new MockHttpClient(function (string $method, string $url, array $options) {
                $this->assertSame(
                    ['cOnTeNt-TyPe: application/vnd.example+json'],
                    $options['normalized_headers']['content-type']
                );

                return new MockResponse('OK');
            });

            $client = new LoggableHttpClient($mock);
            $response = $client->request('POST', 'https://example.com', ['json' => [], 'headers' => $headers]);

            $this->assertSame('OK', $response->getContent());
        }
    }

    public function testJsonPreservesOtherHeadersAndClientDefaults(): void
    {
        $mock = new MockHttpClient(function (string $method, string $url, array $options) {
            $this->assertSame(['Content-Type: application/json'], $options['normalized_headers']['content-type']);
            $this->assertSame(['Accept: application/json'], $options['normalized_headers']['accept']);
            $this->assertSame(['X-Request-Id: example'], $options['normalized_headers']['x-request-id']);

            return new MockResponse('OK');
        });
        $mock = $mock->withOptions(['headers' => ['Accept' => 'application/json']]);

        $client = new LoggableHttpClient($mock);
        $response = $client->request('POST', 'https://example.com', [
            'json' => [],
            'headers' => ['X-Request-Id' => 'example'],
        ]);

        $this->assertSame('OK', $response->getContent());
    }

    public function testDefaultBodyIsSentAndLogged(): void
    {
        $mock = new MockHttpClient(function (string $method, string $url, array $options) {
            $this->assertSame('default-body', $options['body']);

            return new MockResponse('OK');
        });
        $logger = new TestLogger();
        $client = (new LoggableHttpClient($mock))->withOptions(['body' => 'default-body']);
        $client->setLogger($logger);

        $response = $client->request('POST', 'https://example.com');

        $this->assertSame('default-body', $response->getInfo('request_body')->getContent());
        $this->assertSame('OK', $response->getContent());
        $this->assertSame('default-body', $logger->logs[0]['request-content']);
    }

    public function testDefaultJsonIsSentAndLoggedWithCustomHeaders(): void
    {
        $mock = new MockHttpClient(function (string $method, string $url, array $options) {
            $this->assertSame('{"name":"Libor"}', $options['body']);
            $this->assertSame(
                ['Content-Type: application/vnd.example+json'],
                $options['normalized_headers']['content-type']
            );

            return new MockResponse('OK');
        });
        $logger = new TestLogger();
        $client = (new LoggableHttpClient($mock))->withOptions([
            'json' => ['name' => 'Libor'],
            'headers' => ['Content-Type' => 'application/vnd.example+json'],
        ]);
        $client->setLogger($logger);

        $response = $client->request('POST', 'https://example.com');

        $this->assertSame(['name' => 'Libor'], $response->getInfo('request_json'));
        $this->assertSame('{"name":"Libor"}', $response->getInfo('request_body')->getContent());
        $this->assertSame('OK', $response->getContent());
        $this->assertSame(['name' => 'Libor'], $logger->logs[0]['request-content-json']);
    }

    public function testRequestCanOverrideDefaultPayload(): void
    {
        $cases = [
            [['body' => 'default'], ['body' => 'override'], 'override', null],
            [['json' => ['name' => 'default']], ['json' => ['name' => 'override']], '{"name":"override"}', ['name' => 'override']],
            [['json' => ['name' => 'default']], ['json' => null, 'body' => 'override'], 'override', null],
            [['body' => 'default'], ['body' => null, 'json' => ['name' => 'override']], '{"name":"override"}', ['name' => 'override']],
        ];

        foreach ($cases as [$defaults, $options, $expectedBody, $expectedJson]) {
            $mock = new MockHttpClient(function (string $method, string $url, array $options) use ($expectedBody) {
                $this->assertSame($expectedBody, $options['body']);

                return new MockResponse('OK');
            });
            $client = (new LoggableHttpClient($mock))->withOptions($defaults);

            $response = $client->request('POST', 'https://example.com', $options);

            $this->assertSame($expectedBody, $response->getInfo('request_body')->getContent());
            $this->assertSame($expectedJson, $response->getInfo('request_json'));
            $this->assertSame('OK', $response->getContent());
        }
    }

    public function testChainedWithOptionsPreservesBodyAndHeaders(): void
    {
        $mock = new MockHttpClient(function (string $method, string $url, array $options) {
            $this->assertSame('default-body', $options['body']);
            $this->assertSame('https://example.com/path', $url);
            $this->assertSame(['Accept: text/plain'], $options['normalized_headers']['accept']);
            $this->assertSame(['X-Request-Id: example'], $options['normalized_headers']['x-request-id']);

            return new MockResponse('OK');
        });
        $original = new LoggableHttpClient($mock);
        $client = $original->withOptions([
            'body' => 'default-body',
            'base_uri' => 'https://example.com',
            'headers' => ['Accept' => 'text/plain'],
        ])->withOptions(['headers' => ['X-Request-Id' => 'example']]);

        $response = $client->request('POST', '/path');

        $this->assertNotSame($original, $client);
        $this->assertSame('default-body', $response->getInfo('request_body')->getContent());
        $this->assertSame('OK', $response->getContent());
    }

}
