<?php

namespace Liborm85\LoggableHttpClient\Tests;

use Liborm85\LoggableHttpClient\Context\InfoContextInterface;
use Liborm85\LoggableHttpClient\Context\RequestContextInterface;
use Liborm85\LoggableHttpClient\Context\ResponseContextInterface;
use Psr\Log\AbstractLogger;

class TestLogger extends AbstractLogger
{

    /**
     * @var array
     */
    public $logs = [];

    /**
     * @param mixed $level
     * @param string $message
     */
    public function log($level, $message, array $context = []): void
    {
        $log = [
            'level' => $level,
            'message' => $message,
        ];

        if (isset($context['request']) && ($context['request'] instanceof RequestContextInterface)) {
            $log['request-content'] = $context['request']->getContent();

            $requestContentJson = $this->fromJson($context['request']->getContent());
            if ($requestContentJson !== null) {
                $log['request-content-json'] = $requestContentJson;
            }

            $requestStreamContent = $this->getContentFromStream($context['request']->toStream());
            if (is_string($requestStreamContent)) {
                $log['request-content-from-stream'] = $requestStreamContent;
            }

            $log['request-method'] = $context['request']->getRequestMethod();
            $log['request-url'] = $context['request']->getUrl();
            $log['request-headers'] = $context['request']->getHeaders();
            $log['request-headers-string'] = $context['request']->getHeadersAsString();
            $log['request-headers-content-type'] = $context['request']->getHeaders()['content-type'] ?? null;
            $log['request-time'] = $context['request']->getRequestTime() === null ?
                null : $context['request']->getRequestTime()->format(DATE_RFC3339_EXTENDED);
            $log['request-time-datetime'] = $context['request']->getRequestTime();
        }

        if (isset($context['response']) && ($context['response'] instanceof ResponseContextInterface)) {
            $log['response-content'] = $context['response']->getContent();

            $responseContentJson = $this->fromJson($context['response']->getContent());
            if ($responseContentJson !== null) {
                $log['response-content-json'] = array_filter($responseContentJson,
                    function ($key) {
                        return !in_array($key, ['REMOTE_ADDR', 'REMOTE_PORT',]);
                    }, ARRAY_FILTER_USE_KEY);
            }

            $responseStreamContent = $this->getContentFromStream($context['response']->toStream());
            if (is_string($responseStreamContent)) {
                $log['response-content-from-stream'] = $responseStreamContent;
            }

            $log['response-headers'] = $context['response']->getHeaders();
            $log['response-headers-string'] = $context['response']->getHeadersAsString();
            $log['response-headers-content-type'] = $context['response']->getHeaders()['content-type'] ?? null;
            $log['response-time'] = $context['response']->getResponseTime() === null ?
                null : $context['response']->getResponseTime()->format(DATE_RFC3339_EXTENDED);
            $log['response-time-datetime'] = $context['response']->getResponseTime();
        }

        if (isset($context['info']) && ($context['info'] instanceof InfoContextInterface)) {
            $log['info-canceled'] = $context['info']->getInfo('canceled');

            $error = $context['info']->getInfo('error');
            if ($error !== null) {
                $log['info-error'] = $context['info']->getInfo('error');
            }

            $log['info-url'] = $context['info']->getInfo('url');
            $log['info-http_method'] = $context['info']->getInfo('http_method');
            $log['info-http_code'] = $context['info']->getInfo('http_code');
        }

        $this->logs[] = $log;
    }

    private function fromJson(?string $content): ?array
    {
        if ($content === null) {
            return null;
        }

        $json = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        return null;
    }

    /**
     * @param resource|null $stream
     * @return string|false
     */
    private function getContentFromStream($stream)
    {
        if ($stream === null) {
            return false;
        }

        return stream_get_contents($stream);
    }

}
