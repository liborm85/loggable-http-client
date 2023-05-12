<?php

namespace Liborm85\LoggableHttpClient\Context;

use Liborm85\LoggableHttpClient\Body\BodyInterface;
use Liborm85\LoggableHttpClient\Response\LoggableResponse;

final class ResponseContext implements ResponseContextInterface, BodyInterface
{

    use DateTimeTrait;

    private LoggableResponse $response;

    public function __construct(LoggableResponse $response)
    {
        $this->response = $response;
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function getResponseTime(): ?\DateTimeInterface
    {
        return $this->unixTimeToDateTime($this->response->getInfo('response_time'));
    }

    public function getHeaders(): ?array
    {
        try {
            return $this->response->getHeaders(false);
        } catch (\Throwable) {
            return null;
        }
    }

    public function getHeadersAsString(): ?string
    {
        $headers = $this->response->getInfo('response_headers');
        if ($headers !== null) {
            return implode("\n", $headers);
        }

        $headersAsArray = $this->getHeaders();
        if ($headersAsArray === null) {
            return null;
        }

        $headers = [];
        foreach ($this->getHeaders() ?? [] as $key => $header) {
            foreach ($header as $item) {
                $headers[] = "$key: $item";
            }
        }

        return implode("\n", $headers);
    }

    public function getContent(): ?string
    {
        try {
            return $this->response->getContent(false);
        } catch (\Throwable) {
            return null;
        }
    }

    public function __toString(): string
    {
        return $this->getContent() ?? '';
    }

    /**
     * @return resource|null
     */
    public function toStream()
    {
        try {
            $this->response->getContent(false); // load content

            return $this->response->toStream(false);
        } catch (\Throwable) {
            return null;
        }
    }

}
