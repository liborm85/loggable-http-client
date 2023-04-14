<?php

namespace Liborm85\LoggableHttpClient;

use Liborm85\LoggableHttpClient\Body\RequestBody;
use Liborm85\LoggableHttpClient\Response\LoggableResponse;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\DecoratorTrait;
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

    /**
     * @var ?LoggerInterface
     */
    private $logger = null;

    /**
     * @var array
     */
    private $info = [];

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;

        if ($this->client instanceof LoggerAwareInterface) {
            $this->client->setLogger($logger);
        }
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        if (!isset($options['extra'])) {
            $options['extra'] = [];
        }

        if (!isset($options['extra']['curl'])) {
            $options['extra']['curl'] = [];
        }

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
     * @param ResponseInterface|iterable $responses
     */
    public function stream($responses, float $timeout = null): ResponseStreamInterface
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

        $options['body'] = $normalizedOptions['body'];

        if (isset($options['json'])) {
            unset($options['json']);
        }

        return $options;
    }

}
