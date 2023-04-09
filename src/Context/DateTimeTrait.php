<?php


namespace Liborm85\LoggableHttpClient\Context;

trait DateTimeTrait
{
    private function unixTimeToDateTime(?float $microtime): ?\DateTimeImmutable
    {
        if (is_null($microtime)) {
            return null;
        }

        $datetime = \DateTimeImmutable::createFromFormat('U.u', number_format($microtime, 6, '.', '')) ?: null;
        if ($datetime === null) {
            return null;
        }

        return $datetime->setTimezone(new \DateTimeZone(date_default_timezone_get()));
    }
}
