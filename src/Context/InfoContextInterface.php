<?php

namespace Liborm85\LoggableHttpClient\Context;

interface InfoContextInterface
{

    /**
     * @return mixed
     */
    public function getInfo(string $type = null);

}
