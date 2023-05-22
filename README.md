# le7-http-client
Http client client for le7 PHP MVC framework or any PSR project
This is an simple PSR http-client implementation

# API Request Utility

This project provides HTTP clients that uses Curl and standard PHP functions to
make requests.

## What it can?

- Create HTTP requests using cURL
- Create HTTP requests using standard PHP tools

## Requirements

- PHP 8.0 or higher
- Composer (for installing dependencies)

## Installation

1. Install via composer:

```shell
composer require rnr1721/le7-http-client
```

## Testing

```shell
composer test
```

## Usage

In this example, I use Nyholm PSR library, but you can use any, Guzzle for
example

### Get ClientInterface object (httpClient)

```php
use Nyholm\Psr7\Factory\Psr17Factory;
use Core\HttpClient\HttpClientCurl;
use Core\HttpClient\HttpClientDefault;

// Create PSR factories. Nyholm realisation is a single factory to all
$psr17Factory = new Psr17Factory();

// Get Curl http client
$httpClientCurl = new HttpClientCurl(
    $psr17Factory // ResponseFactoryInterface
)

// Or if need get PHP http client
$httpClientPhp = new HttpClientDefault(
    $psr17Factory // ResponseFactoryInterface
)

// now we can use it:

$request = $psr17Factory->createRequest('GET', 'http://tnyholm.se');

$response = $httpClientCurl->sendRequest($request);

```
