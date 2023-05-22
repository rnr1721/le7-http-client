<?php

use Nyholm\Psr7\Factory\Psr17Factory;
use Core\HttpClient\HttpClientDefault;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class HttpClientDefaultTest extends TestCase
{

    public function testSendRequest()
    {
        $responseFactory = new Psr17Factory();
        $httpClient = new HttpClientDefault($responseFactory);

        $fakeResponse = $responseFactory->createResponse()
                ->withStatus(200)
                ->withHeader('Content-Type', 'text/html; charset=UTF-8')
                ->withBody($responseFactory->createStream('Hello, World!'));

        $httpClient->setFakeResponse($fakeResponse);

        $uri = $this->createMock(UriInterface::class);
        $uri->method('__toString')->willReturn('http://example.com');

        $request = $this->createMock(RequestInterface::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);

        $response = $httpClient->sendRequest($request);

        $this->assertEquals($fakeResponse->getStatusCode(), $response->getStatusCode());
        $this->assertEquals($fakeResponse->getHeaderLine('Content-Type'), $response->getHeaderLine('Content-Type'));
        $this->assertEquals((string) $fakeResponse->getBody(), (string) $response->getBody());
    }

    public function testFormatHeaders()
    {
        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $httpClient = new HttpClientDefault($responseFactory);

        $headers = [
            'Content-Type' => ['application/json'],
            'Authorization' => ['Bearer token123'],
            'User-Agent' => ['MyApp/1.0'],
        ];

        $expectedFormattedHeaders = "Content-Type: application/json\r\n";
        $expectedFormattedHeaders .= "Authorization: Bearer token123\r\n";
        $expectedFormattedHeaders .= "User-Agent: MyApp/1.0\r\n";

        $formattedHeaders = $this->invokePrivateMethod($httpClient, 'formatHeaders', [$headers]);

        $this->assertSame($expectedFormattedHeaders, $formattedHeaders);
    }

    private function invokePrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

}
