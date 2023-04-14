<?php

namespace Liborm85\LoggableHttpClient\Body;

use Symfony\Component\HttpClient\Exception\TransportException;

final class RequestBody implements BodyInterface
{

    /**
     * @var int
     */
    private static $CHUNK_SIZE = 16372;

    /**
     * @var int
     */
    private static $STREAM_MAX_MEMORY = 5 * 1024 * 1024;

    /**
     * @var \Closure|resource|string
     */
    private $body;

    /**
     * @param \Closure|resource|string $body
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
     * @param \Closure|resource|string $body
     */
    private function getBodyAsString($body): string
    {
        if (\is_resource($body)) {
            return stream_get_contents($body) ?: '';
        }

        if (!$body instanceof \Closure) {
            return $body;
        }

        $result = '';

        while ('' !== $data = $body(self::$CHUNK_SIZE)) {
            if (!\is_string($data)) {
                throw new TransportException(
                    sprintf(
                        'Return value of the "body" option callback must be string, "%s" returned.',
                        get_debug_type($data)
                    )
                );
            }

            $result .= $data;
        }

        return $result;
    }

    /**
     * @param \Closure|resource|string $body
     * @return resource
     */
    private function getBodyAsResource($body)
    {
        if (\is_resource($body)) {
            return $body;
        }

        $maxmemory = self::$STREAM_MAX_MEMORY;
        $stream = fopen("php://temp/maxmemory:$maxmemory", 'r+');

        if (!$body instanceof \Closure) {
            fwrite($stream, $body);
            rewind($stream);

            return $stream;
        }

        while ('' !== $data = $body(self::$CHUNK_SIZE)) {
            if (!\is_string($data)) {
                throw new TransportException(
                    sprintf(
                        'Return value of the "body" option callback must be string, "%s" returned.',
                        get_debug_type($data)
                    )
                );
            }

            fwrite($stream, $data);
        }

        rewind($stream);

        return $stream;
    }

}
