<?php

namespace Liborm85\LoggableHttpClient\Response;

use Liborm85\LoggableHttpClient\Context\InfoContext;
use Liborm85\LoggableHttpClient\Context\RequestContext;
use Liborm85\LoggableHttpClient\Context\ResponseContext;
use Liborm85\LoggableHttpClient\Internal\DecoratorTrace;
use Liborm85\LoggableHttpClient\LoggableHttpClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\EventSourceHttpClient;
use Symfony\Component\HttpClient\Response\StreamableInterface;
use Symfony\Component\HttpClient\Response\StreamWrapper;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class LoggableResponse implements ResponseInterface, StreamableInterface
{

    private HttpClientInterface $client;

    private ResponseInterface $response;

    private array $info = [];

    private ?LoggerInterface $logger;

    private bool $isResponseContentLogged = false;

    private bool $isAllowedLogResponseContent;

    public function __construct(
        HttpClientInterface $client,
        ResponseInterface $response,
        array &$info,
        ?LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->response = $response;
        $this->info = &$info;
        $this->logger = $logger;
        $this->isAllowedLogResponseContent = !DecoratorTrace::isOuterDecorator(EventSourceHttpClient::class);
    }

    public function __destruct()
    {
        $this->logResponseContent(true);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getHeaders(bool $throw = true): array
    {
        return $this->response->getHeaders($throw);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getContent(bool $throw = true): string
    {
        if (!array_key_exists('response_time', $this->info)) {
            $this->response->getContent(false); // load content
            $this->setResponseTime();
        }

        $this->logResponseContent();

        return $this->response->getContent($throw);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws DecodingExceptionInterface
     */
    public function toArray(bool $throw = true): array
    {
        if (!array_key_exists('response_time', $this->info)) {
            $this->response->getContent(false); // load content
            $this->setResponseTime();
        }

        $this->logResponseContent();

        return $this->response->toArray($throw);
    }

    public function cancel(): void
    {
        $this->response->cancel();

        $this->logResponseContent();
    }

    public function getInfo(string $type = null): mixed
    {
        if (null !== $type) {
            return $this->info[$type] ?? $this->response->getInfo($type);
        }

        return $this->info + $this->response->getInfo();
    }

    /**
     * @return resource
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function toStream(bool $throw = true)
    {
        if ($throw) {
            // Ensure headers arrived
            $this->getHeaders(true);
        }

        return StreamWrapper::createResource($this, $this->client);
    }

    /**
     * @param iterable<ResponseInterface> $responses
     * @throws TransportExceptionInterface
     *
     * @internal
     */
    public static function stream(HttpClientInterface $client, iterable $responses, ?float $timeout): \Generator
    {
        $wrappedResponses = [];
        $loggableResponseMap = new \SplObjectStorage();

        foreach ($responses as $r) {
            if (!$r instanceof self) {
                throw new \TypeError(
                    sprintf(
                        '"%s::stream()" expects parameter 1 to be an iterable of %s objects, "%s" given.',
                        LoggableHttpClient::class,
                        self::class,
                        get_debug_type($r)
                    )
                );
            }

            $loggableResponseMap[$r->response] = $r;
            $wrappedResponses[] = $r->response;
        }

        foreach ($client->stream($wrappedResponses, $timeout) as $r => $chunk) {
            $loggableResponse = $loggableResponseMap[$r];
            if ($chunk->isLast()) {
                $loggableResponse->setResponseTime();
                $loggableResponse->logResponseContent();
            }
            yield $loggableResponse => $chunk;
        }
    }

    public function __sleep(): array
    {
        throw new \BadMethodCallException('Cannot serialize '.__CLASS__);
    }

    public function __wakeup()
    {
        throw new \BadMethodCallException('Cannot unserialize '.__CLASS__);
    }

    private function setResponseTime(): void
    {
        if (!array_key_exists('response_time', $this->info)) {
            $this->info['response_time'] = microtime(true);
        }
    }

    private function logResponseContent(bool $isDestruct = false): void
    {
        if (!$this->logger || !$this->isAllowedLogResponseContent || $this->isResponseContentLogged) {
            return;
        }

        $this->isResponseContentLogged = true;

        try {
            $this->response->getHeaders(false); // load headers
        } catch (\Throwable) {

        }

        if ($isDestruct) {
            try {
                $this->response->getContent(false); // load content
            } catch (\Throwable $ex) {

            }
        }

        $context = [
            'request' => new RequestContext($this),
            'response' => new ResponseContext($this),
            'info' => new InfoContext($this),
        ];

        $info = $this->getInfo();
        if ($this->getInfo('canceled')) {
            $this->logger->info(
                sprintf('Response content (canceled): "%s %s"', $info['http_code'], $info['url']),
                $context
            );
        } else {
            $this->logger->info(sprintf('Response content: "%s %s"', $info['http_code'], $info['url']), $context);
        }
    }

}
