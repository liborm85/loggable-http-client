<?php

namespace Liborm85\LoggableHttpClient\Context;

interface RequestContextInterface
{

    public function getRequestTime(): ?\DateTimeInterface;

    public function getRequestMethod(): string;

    public function getUrl(): string;

    public function getHeaders(): ?array;

    public function getHeadersAsString(): ?string;

    public function getContent(): ?string;

    public function toArray(): ?array;

    /**
     * @return resource|null
     */
    public function toStream();

    /**
     * @return string
     */
    public function __toString();

}
