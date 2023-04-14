<?php

namespace Liborm85\LoggableHttpClient\Tests;

use Liborm85\LoggableHttpClient\LoggableHttpClient;
use Liborm85\LoggableHttpClient\Response\LoggableResponse;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\HttpClient\Exception\TimeoutException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Test\TestHttpServer;

class CurlHttpClientTest extends TestCase
{

    use AssertTrait;

    public static function setUpBeforeClass(): void
    {
        TestHttpServer::start();
    }

    public function testGetContentStringBody(): void
    {
        $logger = new TestLogger();
        $client = $this->getHttpClient($logger);

        $requestTimeStart = new \DateTimeImmutable();
        $response = $client->request('POST', 'http://127.0.0.1:8057/post', ['body' => 'abc=def']);

        $responseTimeStart = new \DateTimeImmutable();
        $body = json_decode($response->getContent(), true);
        $requestTimeFinish = new \DateTimeImmutable();
        $responseTimeFinish = new \DateTimeImmutable();

        $this->assertSame(['abc' => 'def', 'REQUEST_METHOD' => 'POST'], $body);

        $expected = [
            [
                'message' => 'Request: "POST http://127.0.0.1:8057/post"',
            ],
            [
                'message' => 'Response: "200 http://127.0.0.1:8057/post"',
            ],
            [
                'message' => 'Response content: "200 http://127.0.0.1:8057/post"',
                'request-content' => 'abc=def',
                'response-content-json' => [
                    'abc' => 'def',
                    'REQUEST_METHOD' => 'POST',
                ],
            ],
        ];
        $this->assertSameResponseContentLog($expected, $logger->logs);

        $this->assertDateTime($requestTimeStart, $requestTimeFinish, $logger->logs[2]['request-time-datetime']);
        $this->assertDateTime($responseTimeStart, $responseTimeFinish, $logger->logs[2]['response-time-datetime']);
    }

    public function testGetContentJsonBody(): void
    {
        $logger = new TestLogger();
        $client = $this->getHttpClient($logger);

        $requestTimeStart = new \DateTimeImmutable();
        $response = $client->request('POST', 'http://127.0.0.1:8057/post', ['json' => ['abc' => 'def']]);

        $responseTimeStart = new \DateTimeImmutable();
        $body = json_decode($response->getContent(), true);
        $requestTimeFinish = new \DateTimeImmutable();
        $responseTimeFinish = new \DateTimeImmutable();

        $this->assertSame(['{"abc":"def"}' => '', 'REQUEST_METHOD' => 'POST'], $body);

        $expected = [
            [
                'message' => 'Request: "POST http://127.0.0.1:8057/post"',
            ],
            [
                'message' => 'Response: "200 http://127.0.0.1:8057/post"',
            ],
            [
                'message' => 'Response content: "200 http://127.0.0.1:8057/post"',
                'request-content' => '{"abc":"def"}',
                'response-content-json' => [
                    '{"abc":"def"}' => '',
                    'REQUEST_METHOD' => 'POST',
                ],
            ],
        ];

        $this->assertSameResponseContentLog($expected, $logger->logs);

        $this->assertDateTime($requestTimeStart, $requestTimeFinish, $logger->logs[2]['request-time-datetime']);
        $this->assertDateTime($responseTimeStart, $responseTimeFinish, $logger->logs[2]['response-time-datetime']);
    }

    public function testToArray(): void
    {
        $logger = new TestLogger();
        $client = $this->getHttpClient($logger);

        $requestTimeStart = new \DateTimeImmutable();
        $response = $client->request('POST', 'http://127.0.0.1:8057/post', ['body' => 'abc=def']);

        $responseTimeStart = new \DateTimeImmutable();
        $body = $response->toArray();
        $requestTimeFinish = new \DateTimeImmutable();
        $responseTimeFinish = new \DateTimeImmutable();

        $this->assertSame(['abc' => 'def', 'REQUEST_METHOD' => 'POST'], $body);

        $expected = [
            [
                'message' => 'Request: "POST http://127.0.0.1:8057/post"',
            ],
            [
                'message' => 'Response: "200 http://127.0.0.1:8057/post"',
            ],
            [
                'message' => 'Response content: "200 http://127.0.0.1:8057/post"',
                'request-content' => 'abc=def',
                'response-content-json' => [
                    'abc' => 'def',
                    'REQUEST_METHOD' => 'POST',
                ],
            ],
        ];
        $this->assertSameResponseContentLog($expected, $logger->logs);

        $this->assertDateTime($requestTimeStart, $requestTimeFinish, $logger->logs[2]['request-time-datetime']);
        $this->assertDateTime($responseTimeStart, $responseTimeFinish, $logger->logs[2]['response-time-datetime']);
    }

    public function testStream(): void
    {
        $logger = new TestLogger();
        $client = $this->getHttpClient($logger);

        $requestTimeStart = new \DateTimeImmutable();
        $response = $client->request('POST', 'http://127.0.0.1:8057/post', ['body' => 'abc=def']);

        $responseTimeStart = new \DateTimeImmutable();
        $responseTimeFinish = null;
        $requestTimeFinish = null;
        $content = '';
        foreach ($client->stream($response) as $chunk) {
            if ($chunk->isLast() && ($responseTimeFinish === null)) {
                $requestTimeFinish = new \DateTimeImmutable();
                $responseTimeFinish = new \DateTimeImmutable();
            }

            if (!$chunk->isLast()) {
                $content .= $chunk->getContent();
            }
        }

        $body = json_decode($content, true);

        $this->assertSame(['abc' => 'def', 'REQUEST_METHOD' => 'POST'], $body);

        $expected = [
            [
                'message' => 'Request: "POST http://127.0.0.1:8057/post"',
            ],
            [
                'message' => 'Response: "200 http://127.0.0.1:8057/post"',
            ],
            [
                'message' => 'Response content: "200 http://127.0.0.1:8057/post"',
                'request-content' => 'abc=def',
                'response-content-json' => [
                    'abc' => 'def',
                    'REQUEST_METHOD' => 'POST',
                ],
            ],
        ];
        $this->assertSameResponseContentLog($expected, $logger->logs);

        $this->assertDateTime($requestTimeStart, $requestTimeFinish, $logger->logs[2]['request-time-datetime']);
        $this->assertDateTime($responseTimeStart, $responseTimeFinish, $logger->logs[2]['response-time-datetime']);
    }

    public function testToStream(): void
    {
        $logger = new TestLogger();
        $client = $this->getHttpClient($logger);

        $requestTimeStart = new \DateTimeImmutable();
        /** @var LoggableResponse $response */
        $response = $client->request('POST', 'http://127.0.0.1:8057/post', ['body' => 'abc=def']);

        $responseTimeStart = new \DateTimeImmutable();
        $content = stream_get_contents($response->toStream());
        $requestTimeFinish = new \DateTimeImmutable();
        $responseTimeFinish = new \DateTimeImmutable();

        $this->assertIsNotBool($content);

        $body = json_decode($content, true);

        $this->assertSame(['abc' => 'def', 'REQUEST_METHOD' => 'POST'], $body);

        $expected = [
            [
                'message' => 'Request: "POST http://127.0.0.1:8057/post"',
            ],
            [
                'message' => 'Response: "200 http://127.0.0.1:8057/post"',
            ],
            [
                'message' => 'Response content: "200 http://127.0.0.1:8057/post"',
                'request-content' => 'abc=def',
                'response-content-json' => [
                    'abc' => 'def',
                    'REQUEST_METHOD' => 'POST',
                ],
            ],
        ];
        $this->assertSameResponseContentLog($expected, $logger->logs);

        $this->assertDateTime($requestTimeStart, $requestTimeFinish, $logger->logs[2]['request-time-datetime']);
        $this->assertDateTime($responseTimeStart, $responseTimeFinish, $logger->logs[2]['response-time-datetime']);
    }

    public function testDestroyResponse(): void
    {
        $logger = new TestLogger();
        $client = $this->getHttpClient($logger);

        $requestTimeStart = new \DateTimeImmutable();
        $response = $client->request('POST', 'http://127.0.0.1:8057/post', ['body' => 'abc=def']);

        $responseTimeStart = new \DateTimeImmutable();
        unset($response); // destroy response
        $requestTimeFinish = new \DateTimeImmutable();
        $responseTimeFinish = new \DateTimeImmutable();

        $expected = [
            [
                'message' => 'Request: "POST http://127.0.0.1:8057/post"',
            ],
            [
                'message' => 'Response: "200 http://127.0.0.1:8057/post"',
            ],
            [
                'message' => 'Response content: "200 http://127.0.0.1:8057/post"',
                'request-content' => 'abc=def',
                'response-content-json' => [
                    'abc' => 'def',
                    'REQUEST_METHOD' => 'POST',
                ],
            ],
        ];
        $this->assertSameResponseContentLog($expected, $logger->logs);

        $this->assertDateTime($requestTimeStart, $requestTimeFinish, $logger->logs[2]['request-time-datetime']);
        $this->assertDateTime($responseTimeStart, $responseTimeFinish, $logger->logs[2]['response-time-datetime']);
    }

    public function testCancelResponseAfterRequest(): void
    {
        $logger = new TestLogger();
        $client = $this->getHttpClient($logger);

        $requestTimeStart = new \DateTimeImmutable();
        $response = $client->request('POST', 'http://127.0.0.1:8057/post', ['body' => 'abc=def']);

        $response->cancel();
        unset($response);

        $requestTimeFinish = new \DateTimeImmutable();

        $expected = [
            [
                'message' => 'Request: "POST http://127.0.0.1:8057/post"',
            ],
            [
                'message' => 'Response content (canceled): "0 http://127.0.0.1:8057/post"',
                'request-content' => 'abc=def',
                'response-content' => null,
                'response-headers' => null,
                'info-canceled' => true,
            ],
        ];
        $this->assertSameResponseContentLog($expected, $logger->logs);
        $this->assertDateTime($requestTimeStart, $requestTimeFinish, $logger->logs[1]['request-time-datetime']);
        $this->assertNull($logger->logs[1]['response-time-datetime']);
    }

    public function testCancelResponseAfterGetHeaders(): void
    {
        $logger = new TestLogger();
        $client = $this->getHttpClient($logger);

        $requestTimeStart = new \DateTimeImmutable();
        $response = $client->request('POST', 'http://127.0.0.1:8057/post', ['body' => 'abc=def']);

        $response->getHeaders();
        $response->cancel();
        unset($response);

        $requestTimeFinish = new \DateTimeImmutable();

        $expected = [
            [
                'message' => 'Request: "POST http://127.0.0.1:8057/post"',
            ],
            [
                'message' => 'Response: "200 http://127.0.0.1:8057/post"',
            ],
            [
                'message' => 'Response content (canceled): "200 http://127.0.0.1:8057/post"',
                'request-content' => 'abc=def',
                'response-content' => null,
                'response-headers-content-type' => ['application/json'],
                'info-canceled' => true,
            ],
        ];
        $this->assertSameResponseContentLog($expected, $logger->logs);

        $this->assertDateTime($requestTimeStart, $requestTimeFinish, $logger->logs[2]['request-time-datetime']);
        $this->assertNull($logger->logs[2]['response-time-datetime']);
    }

    public function testCancelResponseAfterGetContent(): void
    {
        $logger = new TestLogger();
        $client = $this->getHttpClient($logger);

        $requestTimeStart = new \DateTimeImmutable();
        $response = $client->request('POST', 'http://127.0.0.1:8057/post', ['body' => 'abc=def']);

        $responseTimeStart = new \DateTimeImmutable();

        $response->getContent();
        $response->cancel();
        unset($response);

        $requestTimeFinish = new \DateTimeImmutable();
        $responseTimeFinish = new \DateTimeImmutable();

        $expected = [
            [
                'message' => 'Request: "POST http://127.0.0.1:8057/post"',
            ],
            [
                'message' => 'Response: "200 http://127.0.0.1:8057/post"',
            ],
            [
                'message' => 'Response content: "200 http://127.0.0.1:8057/post"',
                'request-content' => 'abc=def',
                'response-content-json' => [
                    'abc' => 'def',
                    'REQUEST_METHOD' => 'POST',
                ],
                'response-headers-content-type' => ['application/json'],
                'info-canceled' => false,
            ],
        ];
        $this->assertSameResponseContentLog($expected, $logger->logs);

        $this->assertDateTime($requestTimeStart, $requestTimeFinish, $logger->logs[2]['request-time-datetime']);
        $this->assertDateTime($responseTimeStart, $responseTimeFinish, $logger->logs[2]['response-time-datetime']);
    }

    public function testCancelResponseInStream(): void
    {
        $logger = new TestLogger();
        $client = $this->getHttpClient($logger);

        $requestTimeStart = new \DateTimeImmutable();
        $response = $client->request('POST', 'http://127.0.0.1:8057/post', ['body' => 'abc=def']);

        foreach ($client->stream($response) as $chunk) {
            if ($chunk->isFirst()) {
                $response->cancel();
                break;
            }
        }

        $response->cancel();
        unset($response);

        $requestTimeFinish = new \DateTimeImmutable();

        $expected = [
            [
                'message' => 'Request: "POST http://127.0.0.1:8057/post"',
            ],
            [
                'message' => 'Response: "200 http://127.0.0.1:8057/post"',
            ],
            [
                'message' => 'Response content (canceled): "200 http://127.0.0.1:8057/post"',
                'request-content' => 'abc=def',
                'response-content' => null,
                'response-headers-content-type' => null,
                'info-canceled' => true,
            ],
        ];
        $this->assertSameResponseContentLog($expected, $logger->logs);

        $this->assertDateTime($requestTimeStart, $requestTimeFinish, $logger->logs[2]['request-time-datetime']);
        $this->assertNull($logger->logs[2]['response-time-datetime']);
    }

    private function getHttpClient(LoggerInterface $logger): HttpClientInterface
    {
        $client = new LoggableHttpClient(new CurlHttpClient());
        $client->setLogger($logger);

        return $client;
    }

}
