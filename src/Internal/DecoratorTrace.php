<?php

namespace Liborm85\LoggableHttpClient\Internal;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
final class DecoratorTrace
{

    private function __construct()
    {
    }

    /**
     * @return array<class-string>
     */
    public static function getOuterDecorators(): array
    {
        $classes = array_column(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 'class');
        $classes = array_unique($classes);
        array_shift($classes); // first is this decorator, skip

        return array_filter(
            $classes,
            function ($value) {
                return is_a($value, HttpClientInterface::class, true);
            }
        );
    }

    /**
     * @param class-string $className
     */
    public static function isOuterDecorator(string $className): bool
    {
        return in_array($className, self::getOuterDecorators());
    }

}
