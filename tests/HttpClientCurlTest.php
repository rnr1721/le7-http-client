<?php

use Core\HttpClient\HttpClientCurl;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

class HttpClientCurlTest extends TestCase
{

    public function testSendRequest()
    {
        $responseFactory = new Psr17Factory();
        $httpClient = new HttpClientCurl($responseFactory);

        $fakeResponse = new Response(200, ['Content-Type' => 'text/html'], 'Hello, World!');
        $httpClient->setFakeResponse($fakeResponse);

        $uri = $this->createMock(UriInterface::class);
        $uri->method('__toString')->willReturn('http://example.com');

        $body = $this->createMock(StreamInterface::class);
        $body->method('getSize')->willReturn(0);
        $body->method('getContents')->willReturn('');

        $request = $this->createMock(RequestInterface::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);
        $request->method('getHeaders')->willReturn(['User-Agent' => ['Mozilla/5.0']]);
        $request->method('getBody')->willReturn($body);

        $response = $httpClient->sendRequest($request);

        $this->assertEquals($fakeResponse->getStatusCode(), $response->getStatusCode());
        $this->assertEquals($fakeResponse->getHeaders(), $response->getHeaders());
        $this->assertEquals((string) $fakeResponse->getBody(), (string) $response->getBody());
    }

    public function testFormatHeaders()
    {
        $responseFactory = new Psr17Factory();
        $httpClient = new HttpClientCurl($responseFactory);
        $headers = [
            'Content-Type' => ['application/json'],
            'Authorization' => ['Bearer token123'],
        ];

        $formattedHeaders = $this->invokePrivateMethod($httpClient, 'formatHeaders', [$headers]);

        $expectedFormattedHeaders = [
            'Content-Type: application/json',
            'Authorization: Bearer token123',
        ];

        $this->assertEquals($expectedFormattedHeaders, $formattedHeaders);
    }

    public function testParseResponseHeaders()
    {
        $responseFactory = new Psr17Factory();
        $httpClient = new HttpClientCurl($responseFactory);
        $rawHeaders = [
            'Content-Type: application/json',
            'Set-Cookie: cookie1=value1',
            'Set-Cookie: cookie2=value2',
            'X-Custom-Header: custom value',
        ];

        $parsedHeaders = $this->invokePrivateMethod($httpClient, 'parseResponseHeaders', [$rawHeaders]);

        $expectedParsedHeaders = [
            'Content-Type' => ['application/json'],
            'Set-Cookie' => ['cookie1=value1', 'cookie2=value2'],
            'X-Custom-Header' => ['custom value'],
        ];

        $this->assertEquals($expectedParsedHeaders, $parsedHeaders);
    }

    private function invokePrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

}
