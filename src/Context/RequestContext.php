<?php

namespace Liborm85\LoggableHttpClient\Context;

use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\ResponseInterface;

class RequestContext
{

    use DateTimeTrait;

    /**
     * @var int
     */
    private static $CHUNK_SIZE = 16372;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var \Closure|resource|string
     */
    private $body;

    /**
     * @param string|resource|\Closure $body
     */
    public function __construct(ResponseInterface $response, $body)
    {
        $this->response = $response;
        $this->body = $body;
    }

    public function getRequestTime(): ?\DateTimeInterface
    {
        return $this->unixTimeToDateTime($this->response->getInfo('start_time'));
    }

    public function getHeaders(): array
    {
        $headers = [];
        foreach (explode("\n", $this->getHeadersAsString()) as $header) {
            if ($header === '') {
                continue;
            }

            $explodedHeader = explode(':', $header, 2);
            if (count($explodedHeader) !== 2) {
                continue;
            }

            $key = strtolower(trim($explodedHeader[0]));
            $value = trim($explodedHeader[1]);

            if (!array_key_exists($key, $headers)) {
                $headers[$key] = [];
            }

            $headers[$key][] = $value;
        }

        return $headers;
    }

    public function getHeadersAsString(): string
    {
        try {
            $this->response->getHeaders(false); // load headers
        } catch (\Throwable $ex) {

        }

        return $this->response->getInfo('request_header') ?? '';
    }

    public function getContent(): string
    {
        try {
            return $this->getBodyAsString($this->body);
        } catch (\Throwable $ex) {
            return '';
        }
    }

    /**
     * @param \Closure|resource|string $body
     */
    private function getBodyAsString($body): string
    {
        if (\is_resource($body)) {
            return stream_get_contents($body);
        }

        if (!$body instanceof \Closure) {
            return $body;
        }

        $result = '';

        while ('' !== $data = $body(self::$CHUNK_SIZE)) {
            if (!\is_string($data)) {
                throw new TransportException(sprintf('Return value of the "body" option callback must be string, "%s" returned.',
                    get_debug_type($data)));
            }

            $result .= $data;
        }

        return $result;
    }

}
