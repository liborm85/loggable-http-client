<?php

namespace Liborm85\LoggableHttpClient\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\HttpClient\DecoratorTrait;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Test\TestHttpServer;

class SymfonyHttpClientBugTest extends TestCase
{

    private function getIterableYieldContent(): iterable
    {
        yield 'abc';
        yield '=';
        yield 'd';
        yield 'e';
        yield 'f';
    }

    public static function setUpBeforeClass(): void
    {
        TestHttpServer::start();
    }

    public function testSymfonyHttpClientSupportsSimpleIterableYield(): void
    {
        $client = new CurlHttpClient(
            [
                'verify_host' => false,
                'verify_peer' => false,
            ]
        );

        $response = $client->request(
            'POST',
            'http://127.0.0.1:8057/post',
            ['body' => $this->getIterableYieldContent()]
        );

        $body = json_decode($response->getContent(), true);

        $this->assertSame(['abc' => 'def', 'REQUEST_METHOD' => 'POST'], $body);
    }

    /**
     * Symfony HTTP client contains bug for body if is is yield function (body can be returned only once).
     * Symfony doesn't solve this in any way and if you use RetryableHttpClient for first request is body available
     * but for second and every other send empty string.
     *
     * @depends testSymfonyHttpClientSupportsSimpleIterableYield
     */
    public function testRetryableHttpClientIterableYieldBugStillContains(): void
    {
        $client = new CurlHttpClient(
            [
                'verify_host' => false,
                'verify_peer' => false,
            ]
        );

        $client = new RetryableHttpClient(
            $client,
            new GenericRetryStrategy([200], 0),
            3
        );

        $response = $client->request(
            'POST',
            'http://127.0.0.1:8057/post',
            ['body' => $this->getIterableYieldContent()]
        );

        $body = json_decode($response->getContent(), true);

        // expected this body:
        // $this->assertSame(['abc' => 'def', 'REQUEST_METHOD' => 'POST'], $body);
        // but return this:
        $this->assertSame(['content-type' => 'application/x-www-form-urlencoded', 'REQUEST_METHOD' => 'POST'], $body);
    }

}
