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

        $decorators = array_filter(
            $classes,
            function ($value) {
                return is_a($value, HttpClientInterface::class, true);
            }
        );

        array_shift($decorators); // first is this decorator, skip

        return $decorators;
    }

    /**
     * @param class-string $className
     */
    public static function isOuterDecorator(string $className): bool
    {
        return in_array($className, self::getOuterDecorators());
    }

    /**
     * @return array<class-string>
     */
    public static function getInnerDecorators(HttpClientInterface $client): array
    {
        $current = $client;

        $clients = [];
        while ($current) {
            $clients[] = get_class($current);

            $current = self::getInnerClient($current);
        }

        return $clients;
    }

    /**
     * @param class-string $className
     */
    public static function isInnerDecorator(string $className, HttpClientInterface $client): bool
    {
        return in_array($className, self::getInnerDecorators($client));
    }

    private static function getInnerClient(HttpClientInterface $client): ?HttpClientInterface
    {
        $reflection = new \ReflectionClass($client);

        if ($reflection->hasProperty('client')) { // get from client property
            $property = $reflection->getProperty('client');
            $property->setAccessible(true);
            $value = $property->getValue($client);
            if ($value instanceof HttpClientInterface) {
                return $value;
            }
        }

        foreach ($reflection->getProperties() as $property) { // get from other HttpClientInterface property
            $property->setAccessible(true);
            $value = $property->getValue($client);
            if ($value instanceof HttpClientInterface) {
                return $value;
            }
        }

        return null;
    }

}
