<?php

namespace Liborm85\LoggableHttpClient\Context;

use Symfony\Contracts\HttpClient\ResponseInterface;

final class InfoContext
{

    /**
     * @var ResponseInterface
     */
    private $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * @return mixed
     */
    public function getInfo(string $type = null)
    {
        return $this->response->getInfo($type);
    }

}
