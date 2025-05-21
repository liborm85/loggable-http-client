<?php

namespace Liborm85\LoggableHttpClient\Context;

interface ResponseContextInterface extends \Stringable
{

    public function getStatusCode(): int;

    public function getResponseTime(): ?\DateTimeInterface;

    public function getHeaders(): ?array;

    public function getHeadersAsString(): ?string;

    public function getContent(): ?string;

    public function toArray(): ?array;

    /**
     * @return resource|null
     */
    public function toStream();

    public function __toString(): string;

}
