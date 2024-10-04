<?php

namespace Liborm85\LoggableHttpClient;

use Liborm85\LoggableHttpClient\Body\RequestBody;
use Liborm85\LoggableHttpClient\Response\LoggableResponse;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\DecoratorTrait;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\HttpClientTrait;
use Symfony\Component\HttpClient\Response\ResponseStream;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Symfony\Contracts\Service\ResetInterface;

final class LoggableHttpClient implements HttpClientInterface, ResetInterface, LoggerAwareInterface
{

    use DecoratorTrait, HttpClientTrait {
        DecoratorTrait::withOptions insteadof HttpClientTrait;
    }

    private ?LoggerInterface $logger = null;

    private array $info = [];

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;

        if ($this->client instanceof LoggerAwareInterface) {
            $this->client->setLogger($logger);
        }
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $options['buffer'] = true;

        if (defined('CURLINFO_HEADER_OUT')) {
            $options['extra']['curl'][CURLINFO_HEADER_OUT] = true;
        }

        $thisInfo = &$this->info;

        if (isset($options['json'])) {
            $thisInfo['request_json'] = $options['json'];
        }

        $options = $this->normalizeOptions($options);

        if (isset($options['body'])) {
            $thisInfo['request_body'] = new RequestBody($options['body']);
        }

        $onProgress = $options['on_progress'] ?? null;
        if ($onProgress !== null) {
            $options['on_progress'] = static function (int $dlNow, int $dlSize, array $info) use (
                &$thisInfo,
                $onProgress
            ) {
                $onProgress($dlNow, $dlSize, $thisInfo + $info);
            };
        }

        $response = $this->client->request($method, $url, $options);

        return new LoggableResponse($this, $response, $thisInfo, $this->logger);
    }

    /**
     * @param LoggableResponse|iterable<LoggableResponse> $responses
     */
    public function stream(ResponseInterface|iterable $responses, ?float $timeout = null): ResponseStreamInterface
    {
        if ($responses instanceof LoggableResponse) {
            $responses = [$responses];
        }

        return new ResponseStream(LoggableResponse::stream($this->client, $responses, $timeout));
    }

    private function normalizeOptions(array $options): array
    {
        if (!isset($options['body']) && !isset($options['json'])) {
            return $options;
        }

        [, $normalizedOptions] = self::prepareRequest(
            null,
            null,
            [
                'body' => $options['body'] ?? null,
                'json' => $options['json'] ?? null,
            ]
        );

        $options['body'] = $this->fixBody($normalizedOptions['body']);

        if (isset($options['json'])) {
            unset($options['json']);
        }

        return $options;
    }

    /**
     * Symfony HTTP client contains bug for body if is is yield function (body can be returned only once).
     * Symfony doesn't solve this in any way and if you use RetryableHttpClient for first request is body available
     * but for second and every other send empty string.
     *
     * @param \Closure|resource|string $body
     * @return resource|string
     */
    private function fixBody($body)
    {
        if (!$body instanceof \Closure) {
            return $body;
        }

        /** @var resource $stream */
        $stream = fopen("php://temp", 'r+');

        while ('' !== $data = $body(self::$CHUNK_SIZE)) {
            if (!\is_string($data)) {
                throw new TransportException(
                    sprintf(
                        'Return value of the "body" option callback must be string, "%s" returned.',
                        get_debug_type($data)
                    )
                );
            }

            fwrite($stream, $data);
        }

        rewind($stream);

        return $stream;
    }

}
