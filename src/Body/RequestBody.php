<?php

namespace Liborm85\LoggableHttpClient\Body;

final class RequestBody implements BodyInterface
{

    /**
     * @var resource|string
     */
    private $body;

    /**
     * @param resource|string $body
     */
    public function __construct($body)
    {
        $this->body = $body;
    }

    public function getContent(): ?string
    {
        return $this->getBodyAsString($this->body);
    }

    /**
     * @return resource|null
     */
    public function toStream()
    {
        return $this->getBodyAsResource($this->body);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getContent() ?? '';
    }

    /**
     * @param resource|string $body
     */
    private function getBodyAsString($body): string
    {
        if (\is_resource($body)) {
            rewind($body);

            return stream_get_contents($body) ?: '';
        }

        return $body;
    }

    /**
     * @param resource|string $body
     * @return resource
     */
    private function getBodyAsResource($body)
    {
        if (\is_resource($body)) {
            rewind($body);

            return $body;
        }

        /** @var resource $stream */
        $stream = fopen("php://temp", 'r+');

        fwrite($stream, $body);
        rewind($stream);

        return $stream;
    }

}
