<?php

namespace Liborm85\LoggableHttpClient\Context;

use Liborm85\LoggableHttpClient\Body\BodyInterface;
use Liborm85\LoggableHttpClient\Response\LoggableResponse;

final class RequestContext implements RequestContextInterface, BodyInterface
{

    use DateTimeTrait;

    private LoggableResponse $response;

    public function __construct(LoggableResponse $response)
    {
        $this->response = $response;
    }

    public function getRequestTime(): ?\DateTimeInterface
    {
        return $this->unixTimeToDateTime($this->response->getInfo('start_time'));
    }

    public function getRequestMethod(): string
    {
        return $this->response->getInfo('http_method');
    }

    public function getUrl(): string
    {
        return $this->response->getInfo('url');
    }

    public function getHeaders(): ?array
    {
        $headersAsString = $this->getHeadersAsString();
        if ($headersAsString === null) {
            return null;
        }

        $headers = [];
        foreach (explode("\n", $headersAsString) as $header) {
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

    public function getHeadersAsString(): ?string
    {
        try {
            $this->response->getHeaders(false); // load headers
        } catch (\Throwable) {
            return null;
        }

        return $this->response->getInfo('request_header') ?? null;
    }

    public function getContent(): ?string
    {
        $requestBody = $this->getRequestBody();
        if ($requestBody === null) {
            return null;
        }

        try {
            return $requestBody->getContent();
        } catch (\Throwable) {
            return null;
        }
    }

    public function toArray(): ?array
    {
        $requestBody = $this->getRequestBody();
        if ($requestBody === null) {
            return null;
        }

        try {
            return $requestBody->toArray();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return resource|null
     */
    public function toStream()
    {
        $requestBody = $this->getRequestBody();
        if ($requestBody === null) {
            return null;
        }

        try {
            return $requestBody->toStream();
        } catch (\Throwable) {
            return null;
        }
    }

    public function __toString(): string
    {
        return $this->getContent() ?? '';
    }

    private function getRequestBody(): ?BodyInterface
    {
        $requestBody = $this->response->getInfo('request_body');

        if ($requestBody instanceof BodyInterface) {
            return $requestBody;
        }

        return null;
    }

}
