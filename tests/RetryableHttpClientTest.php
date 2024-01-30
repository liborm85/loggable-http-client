<?php

namespace Liborm85\LoggableHttpClient\Tests;

use Liborm85\LoggableHttpClient\LoggableHttpClient;
use Liborm85\LoggableHttpClient\Response\LoggableResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;

class RetryableHttpClientTest extends TestCase
{

    use AssertTrait;

    public function testRetry(): void
    {
        $logger = new TestLogger();

        $mockClient = new MockHttpClient(
            [
                new MockResponse('mock A', ['http_code' => 500]),
                new MockResponse('mock B', ['http_code' => 500]),
                new MockResponse('mock C', ['http_code' => 200]),
            ]
        );

        $loggableClient = new LoggableHttpClient($mockClient);
        $loggableClient->setLogger($logger);

        $client = new RetryableHttpClient(
            $loggableClient,
            new GenericRetryStrategy([500], 0),
            3
        );

        $requestTimeStart = new \DateTimeImmutable();
        $response = $client->request('POST', 'https://example.com/foo-bar', ['body' => 'abc=def']);

        $responseTimeStart = new \DateTimeImmutable();
        $body = $response->getContent();
        $requestTimeFinish = new \DateTimeImmutable();
        $responseTimeFinish = new \DateTimeImmutable();

        $this->assertSame('mock C', $body);

        $expected = [
            [
                'message' => 'Response content (canceled): "500 https://example.com/foo-bar"',
                'request-content' => 'abc=def',
                'response-content' => null,
            ],
            [
                'message' => 'Response content (canceled): "500 https://example.com/foo-bar"',
                'request-content' => 'abc=def',
                'response-content' => null,
            ],
            [
                'message' => 'Response content: "200 https://example.com/foo-bar"',
                'request-content' => 'abc=def',
                'response-content' => 'mock C',
            ],
        ];
        $this->assertSameResponseContentLog($expected, $logger->logs);

        $this->assertDateTime($requestTimeStart, $requestTimeFinish, $logger->logs[2]['request-time-datetime']);
        $this->assertDateTime($responseTimeStart, $responseTimeFinish, $logger->logs[2]['response-time-datetime']);
    }

    public function testHttp404(): void
    {
        $logger = new TestLogger();

        $mockClient = new MockHttpClient(
            [
                new MockResponse('mock A', ['http_code' => 500]),
                new MockResponse('mock B', ['http_code' => 500]),
                new MockResponse('mock C', ['http_code' => 404]),
            ]
        );

        $loggableClient = new LoggableHttpClient($mockClient);
        $loggableClient->setLogger($logger);

        $client = new RetryableHttpClient(
            $loggableClient,
            new GenericRetryStrategy([500], 0),
            3
        );

        $requestTimeStart = new \DateTimeImmutable();
        $response = $client->request('POST', 'https://example.com/foo-bar', ['body' => 'abc=def']);

        $responseTimeStart = new \DateTimeImmutable();
        $body = $response->getContent(false);
        $requestTimeFinish = new \DateTimeImmutable();
        $responseTimeFinish = new \DateTimeImmutable();

        $this->assertSame('mock C', $body);

        $expected = [
            [
                'message' => 'Response content (canceled): "500 https://example.com/foo-bar"',
                'request-content' => 'abc=def',
                'response-content' => null,
            ],
            [
                'message' => 'Response content (canceled): "500 https://example.com/foo-bar"',
                'request-content' => 'abc=def',
                'response-content' => null,
            ],
            [
                'message' => 'Response content: "404 https://example.com/foo-bar"',
                'request-content' => 'abc=def',
                'response-content' => 'mock C',
            ],
        ];
        $this->assertSameResponseContentLog($expected, $logger->logs);

        $this->assertDateTime($requestTimeStart, $requestTimeFinish, $logger->logs[2]['request-time-datetime']);
        $this->assertDateTime($responseTimeStart, $responseTimeFinish, $logger->logs[2]['response-time-datetime']);
    }

    public function testHttp404ThrowHttpException(): void
    {
        $logger = new TestLogger();

        $mockClient = new MockHttpClient(
            [
                new MockResponse('mock A', ['http_code' => 500]),
                new MockResponse('mock B', ['http_code' => 500]),
                new MockResponse('mock C', ['http_code' => 404]),
            ]
        );

        $retryableClient = new RetryableHttpClient(
            $mockClient,
            new GenericRetryStrategy([500], 0),
            3
        );

        $client = new LoggableHttpClient($retryableClient);
        $client->setLogger($logger);

        $requestTimeStart = new \DateTimeImmutable();
        $response = $client->request('POST', 'https://example.com/foo-bar', ['body' => 'abc=def']);

        $responseTimeStart = new \DateTimeImmutable();
        try {
            $response->getContent();
        } catch (HttpExceptionInterface $ex) {
            $this->assertSame(LoggableResponse::class, get_class($ex->getResponse()));

            $this->assertSame('abc=def', (string)$ex->getResponse()->getInfo('request_body'));
            $this->assertIsFloat($ex->getResponse()->getInfo('response_time'));
        }
        $requestTimeFinish = new \DateTimeImmutable();
        $responseTimeFinish = new \DateTimeImmutable();

        $expected = [
            [
                'message' => 'Response content: "404 https://example.com/foo-bar"',
                'request-content' => 'abc=def',
                'response-content' => 'mock C',
            ],
        ];
        $this->assertSameResponseContentLog($expected, $logger->logs);

        $this->assertDateTime($requestTimeStart, $requestTimeFinish, $logger->logs[0]['request-time-datetime']);
        $this->assertDateTime($responseTimeStart, $responseTimeFinish, $logger->logs[0]['response-time-datetime']);
    }

}
