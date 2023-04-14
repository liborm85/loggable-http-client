<?php

namespace Liborm85\LoggableHttpClient\Tests;

trait AssertTrait
{

    final public function assertSameResponseContentLog(array $expected, array $logs): void
    {
        $actual = [];
        foreach ($expected as $index => $expectedItem) {
            if (!array_key_exists($index, $logs)) {
                continue;
            }
            $actual[$index] = [];
            foreach (array_keys($expectedItem) as $key) {
                $actual[$index][$key] = array_key_exists($key, $logs[$index]) ? $logs[$index][$key] : false;
            }
        }

        $this->assertSame($expected, $actual);
    }

    /**
     * @param mixed $actual
     */
    final public function assertDateTime(
        ?\DateTimeInterface $expectedMin,
        ?\DateTimeInterface $expectedMax,
        $actual
    ): void {
        $dateTimeFormat = 'Y-m-d\TH:i:s.uP';
        $this->assertInstanceOf(\DateTimeInterface::class, $expectedMin);
        $this->assertInstanceOf(\DateTimeInterface::class, $expectedMax);
        $this->assertInstanceOf(\DateTimeInterface::class, $actual);

        $this->assertGreaterThanOrEqual($expectedMin->format($dateTimeFormat), $actual->format($dateTimeFormat));

        $this->assertLessThanOrEqual($expectedMax->format($dateTimeFormat), $actual->format($dateTimeFormat));
    }

}
