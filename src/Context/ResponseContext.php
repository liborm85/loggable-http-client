<?php

namespace Liborm85\LoggableHttpClient\Context;

use Symfony\Contracts\HttpClient\ResponseInterface;

final class ResponseContext
{
    use DateTimeTrait;

    /**
     * @var ResponseInterface
     */
    private $response;

    public function __construct(ResponseInterface $response)
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

    public function getHeaders(): array
    {
        try {
            return $this->response->getHeaders(false);
        } catch (\Throwable $ex) {
            return [];
        }
    }

    public function getHeadersAsString(): string
    {
        $headers = $this->response->getInfo('response_headers');
        if ($headers !== null) {
            return implode("\n", $headers);
        }

        $headers = [];
        foreach ($this->getHeaders() as $key => $header) {
            foreach ($header as $item) {
                $headers[] = "$key: $item";
            }
        }

        return implode("\n", $headers);
    }


    public function getContent(): string
    {
        try {
            return $this->response->getContent(false);
        } catch (\Throwable $ex) {
            return '';
        }
    }

}
