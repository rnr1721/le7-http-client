<?php

declare(strict_types=1);

namespace Core\HttpClient;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Core\HttpClient\Exceptions\NetworkException;
use Core\HttpClient\Exceptions\ClientException;
use function stream_context_create,
             fopen,
             fclose,
             trim,
             stream_get_meta_data,
             stream_get_contents;

/**
 * Default HTTP client that using standard PHP tools for requests
 */
class HttpClientDefault implements ClientInterface
{

    use HttpClientTrait;

    /**
     * Factory for create PSR responses
     * @var ResponseFactoryInterface
     */
    private ResponseFactoryInterface $responseFactory;

    /**
     * Fake response for testing purposes
     * @var ResponseInterface|null
     */
    private ?ResponseInterface $fakeResponse = null;

    /**
     * HttpClientDefault constructor.
     *
     * @param ResponseFactoryInterface $responseFactory The response factory implementation.
     */
    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * Send a PSR-7 request and return the response.
     *
     * @param RequestInterface $request The PSR-7 request to send.
     * @return ResponseInterface The PSR-7 response received.
     * @throws NetworkException If sending the request fails.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {

        if ($this->fakeResponse !== null) {
            return $this->fakeResponse;
        }

        $method = $request->getMethod();
        $uri = $request->getUri();
        $headers = $request->getHeaders();
        $body = '';

        $requestBody = $request->getBody();
        if ($requestBody->getSize() > 0) {
            $body = $requestBody->getContents();
        }

        $options = [
            'http' => [
                'method' => $method,
                'header' => $this->formatHeaders($headers),
                'content' => $body,
                'follow_location' => $this->followLocation ? 1 : 0,
                'max_redirects' => $this->maxRedirects,
                'timeout' => $this->timeout,
            ],
        ];

        $context = stream_context_create($options);
        $stream = fopen((string) $uri, 'r', false, $context);

        if ($stream === false) {
            throw new NetworkException('Failed to send request', $request);
        }

        $response = $this->parseResponse($stream);
        fclose($stream);

        return $response;
    }

    /**
     * Format headers array into a string.
     *
     * @param array $headers The headers array.
     * @return string The formatted headers string.
     */
    private function formatHeaders(array $headers): string
    {
        $formattedHeaders = '';
        foreach ($headers as $name => $values) {
            foreach ($values as $value) {
                $formattedName = trim($name);
                $formattedValue = trim($value);
                $formattedHeaders .= "$formattedName: $formattedValue\r\n";
            }
        }
        return $formattedHeaders;
    }

    /**
     * Parse the response from the stream into a PSR-7 response.
     *
     * @param mixed $stream The stream containing the response.
     * @return ResponseInterface The PSR-7 response.
     * @throws ClientException If read response fails.
     */
    private function parseResponse(mixed $stream): ResponseInterface
    {
        $metaData = stream_get_meta_data($stream);
        $headers = $metaData['wrapper_data'];
        $body = stream_get_contents($stream);

        if ($headers === false || $body === false) {
            throw new ClientException('Failed to read response');
        }

        $responseHeaders = $this->parseResponseHeaders($headers);

        $response = $this->responseFactory->createResponse();
        foreach ($responseHeaders as $headerKey => $headerValue) {
            foreach ($headerValue as $value) {
                $response = $response->withAddedHeader($headerKey, $value);
            }
        }

        if (!empty($body)) {
            $response->getBody()->write($body);
        }

        return $response;
    }

    /**
     * Set a fake response for testing purposes.
     *
     * @param ResponseInterface $response The fake response.
     * @return self
     */
    public function setFakeResponse(ResponseInterface $response): self
    {
        $this->fakeResponse = $response;
        return $this;
    }

}
