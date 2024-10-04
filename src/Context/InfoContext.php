<?php

namespace Liborm85\LoggableHttpClient\Context;

use Symfony\Contracts\HttpClient\ResponseInterface;

final class InfoContext implements InfoContextInterface
{

    private ResponseInterface $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function getInfo(?string $type = null): mixed
    {
        return $this->response->getInfo($type);
    }

}
