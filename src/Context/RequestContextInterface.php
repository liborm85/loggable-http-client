<?php

namespace Liborm85\LoggableHttpClient\Context;

interface RequestContextInterface extends \Stringable
{

    public function getRequestTime(): ?\DateTimeInterface;

    public function getRequestMethod(): string;

    public function getUrl(): string;

    public function getHeaders(): ?array;

    public function getHeadersAsString(): ?string;

    public function getContent(): ?string;

    /**
     * @return resource|null
     */
    public function toStream();

    public function __toString(): string;

}
