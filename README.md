# Loggable HTTP Client [![Packagist](https://img.shields.io/packagist/v/liborm85/loggable-http-client.svg)](https://packagist.org/packages/liborm85/loggable-http-client)

Extended logging for the Symfony HTTP Client allowing especially to log the content of the request and response.

Adds events to [PSR-3](https://www.php-fig.org/psr/psr-3/) logger interface:
- `'Response content:'` (level: `info`) - response body content received; in context is available (details in [Usage](#usage)):
  - `request` (`\Liborm85\LoggableHttpClient\Context\RequestContextInterface`)
  - `response` (`\Liborm85\LoggableHttpClient\Context\ResponseContextInterface`)
  - `info` (`\Liborm85\LoggableHttpClient\Context\InfoContextInterface`)
- `'Response content (canceled):'` (level: `info`) - same as above, only for canceled request (not all data may be available)

Adds additional information to `getInfo()` method:
- `request_json` (`mixed`) - `json` option from request `$options` (if is provided)
- `request_body` (`\Liborm85\LoggableHttpClient\Body\BodyInterface`) - `body` option from request `$options` transformed to object (if is provided)
- `response_time` (`float`) - the time when the response was received

<!-- TBD
## Installation

You can install it with:
```
composer require liborm85/loggable-http-client
```
-->

## Usage

```php
<?php

$httpClient = \Symfony\Component\HttpClient\HttpClient::create(); // optional
$loggableHttpClient = new \Liborm85\LoggableHttpClient\LoggableHttpClient($httpClient);
$loggableHttpClient->setLogger(new \MyLogger());

$response = $loggableHttpClient->request('GET', 'https://example.com');


class MyLogger extends \Psr\Log\AbstractLogger
{

    public function log($level, $message, array $context = []): void
    {
        if (isset($context['request']) && ($context['request'] instanceof \Liborm85\LoggableHttpClient\Context\RequestContextInterface)) {
            $context['request']->getContent(); // request content body as string
            (string)$context['request']; // is Stringable, request content body as string
            $context['request']->toStream(); // request content body as PHP stream
            $context['request']->getHeadersAsString(); // request headers as string
            $context['request']->getHeaders(); // request headers as array (string[][])
            $context['request']->getRequestTime(); // request time as DateTimeInterface
            $context['request']->getRequestMethod(); // request HTTP method
            $context['request']->getUrl(); // full request URL
        }

        if (isset($context['response']) && ($context['response'] instanceof \Liborm85\LoggableHttpClient\Context\ResponseContextInterface)) {
            $context['response']->getContent(); // response content body as string
            (string)$context['response']; // is Stringable, response content body as string
            $context['response']->toStream(); // response content body as PHP stream
            $context['response']->getHeadersAsString(); // response headers as string
            $context['response']->getHeaders(); // response headers as array (string[][])
            $context['response']->getResponseTime(); // response time as DateTimeInterface
        }

        if (isset($context['info']) && ($context['info'] instanceof \Liborm85\LoggableHttpClient\Context\InfoContextInterface)) {
            $context['info']->getInfo(); // return all available information
            $context['info']->getInfo('url'); // return one information for provided type
        }
    }

}
```

## License

MIT
