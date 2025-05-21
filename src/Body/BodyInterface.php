<?php

namespace Liborm85\LoggableHttpClient\Body;

interface BodyInterface extends \Stringable
{

    public function getContent(): ?string;

    public function toArray(): ?array;

    /**
     * @return resource|null
     */
    public function toStream();

    public function __toString(): string;

}
