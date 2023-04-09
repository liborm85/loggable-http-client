# [WIP] Loggable HTTP Client

Extended logging for the Symfony HTTP Client.

## Installation

You can install it with:
```
composer require liborm85/loggable-http-client
```

## Requirements

- PHP 7.2.5+
- Symfony HTTP Client 5.4.0+

## Usage

```php
<?php

$httpClient = \Symfony\Component\HttpClient\HttpClient::create(); // optional
$loggableHttpClient = new \Liborm85\LoggableHttpClient\LoggableHttpClient();
$loggableHttpClient->setLogger(new \MyLogger());

$response = $loggableHttpClient->request('GET', 'https://example.com');


class MyLogger extends \Psr\Log\AbstractLogger
{

    public function log($level, $message, array $context = []): void
    {
        if (isset($context['request']) && ($context['request'] instanceof \Liborm85\LoggableHttpClient\Context\RequestContext)) {
            // $context['request']->getContent();
            // $context['request']->toStream();
            // $context['request']->getHeadersAsString();
            // $context['request']->getRequestTime();
            // etc.
        }

        if (isset($context['response']) && ($context['response'] instanceof \Liborm85\LoggableHttpClient\Context\ResponseContext)) {
            // $context['response']->getContent()
            // $context['response']->toStream();
            // $context['response']->getHeadersAsString()
            // $context['response']->getResponseTime()
            // etc.
        }

        if (isset($context['info']) && ($context['info'] instanceof \Liborm85\LoggableHttpClient\Context\InfoContext)) {
            // $context['info']->getInfo();
            // $context['info']->getInfo('url');
            // etc.
        }
    }

}
```

## License

MIT
