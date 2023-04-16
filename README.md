# [WIP] Loggable HTTP Client

Extended logging for the Symfony HTTP Client allowing especially to log the content of the request and response.

Adds events to [PSR-3](https://www.php-fig.org/psr/psr-3/) logger interface:
- `'Response content:'` (level: `info`) - response body content received; in context is available:
  - `request` (`\Liborm85\LoggableHttpClient\Context\RequestContext`) - details in [Usage](#usage)
  - `response` (`\Liborm85\LoggableHttpClient\Context\ResponseContext`) - details in [Usage](#usage)
  - `info` (`\Liborm85\LoggableHttpClient\Context\InfoContext`) - details in [Usage](#usage)
- `'Response content (canceled):'` (level: `info`) - same as above, only for canceled request (not all data may be available)

Adds additional information to `getInfo()` method:
- `request_json` (`mixed`) - `json` option from request `$options` (if is provided)
- `request_body` (`\Liborm85\LoggableHttpClient\Body\RequestBody`) - `body` option from request `$options` transformed to object (if is provided)
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
