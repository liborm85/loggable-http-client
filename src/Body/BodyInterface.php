<?php

namespace Liborm85\LoggableHttpClient\Body;

interface BodyInterface
{

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