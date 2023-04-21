<?php

namespace Liborm85\LoggableHttpClient\Context;

interface ResponseContextInterface
{

    public function getStatusCode(): int;

    public function getResponseTime(): ?\DateTimeInterface;

    public function getHeaders(): ?array;

    public function getHeadersAsString(): ?string;

    public function getContent(): ?string;

    /**
     * @return resource|null
     */
    public function toStream();

    /**
     * @return string
     */
    public function __toString();

}
