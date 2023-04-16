<?php

namespace Liborm85\LoggableHttpClient\Tests;

use Liborm85\LoggableHttpClient\LoggableHttpClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Component\HttpClient\RetryableHttpClient;

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

}
