# Changelog

## Unreleased

- fixed request body stream content `"0"` being converted to an empty string
- fixed sending and logging default request bodies set via `withOptions()`
- fixed missing `Content-Type: application/json` header for JSON requests

## 1.2.1 - 2025-10-14

- fixed exception in `getStatusCode()` after throw `TransportExceptionInterface` exception

## 1.2.0 - 2025-05-27

- added `toArray()` method to convert JSON request/response data into an array for use in the logger's `request`, `response` contexts and `request_body` in info

## 1.1.1 - 2024-11-21

- added PHP 8.4 support

## 1.1.0 - 2024-01-31

- added additional information in `getInfo()` for `getResponse()` after throw `HttpExceptionInterface` exception

## 1.0.0 - 2023-05-12

- initial release
