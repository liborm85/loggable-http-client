<?php

namespace Liborm85\LoggableHttpClient\Context;

interface InfoContextInterface
{

    public function getInfo(string $type = null): mixed;

}
