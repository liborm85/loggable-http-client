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

    public function toArray(): ?array
    {
        $content = $this->getContent();
        if ($content === null || $content === '') {
            return null;
        }

        try {
            $content = json_decode($content, true, 512, \JSON_BIGINT_AS_STRING | \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (!is_array($content)) {
            return null;
        }

        return $content;
    }

    /**
     * @return resource|null
     */
    public function toStream()
    {
        return $this->getBodyAsResource($this->body);
    }

    public function __toString(): string
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

        /** @var string $body */
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

        /** @var string $body */
        fwrite($stream, $body);
        rewind($stream);

        return $stream;
    }

}
