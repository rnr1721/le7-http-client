<?php

declare(strict_types=1);

namespace Core\HttpClient;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Core\HttpClient\Exceptions\ClientException;
use Core\HttpClient\Exceptions\NetworkException;
use function extension_loaded,
             curl_init,
             curl_setopt,
             curl_exec,
             curl_close,
             curl_error,
             curl_getinfo,
             is_string,
             substr,
             explode,
             trim;
use const CURLOPT_CUSTOMREQUEST,
          CURLOPT_RETURNTRANSFER,
          CURLOPT_HEADER,
          CURLOPT_POSTFIELDS,
          CURLOPT_HTTPHEADER,
          CURLOPT_FOLLOWLOCATION,
          CURLOPT_MAXREDIRS,
          CURLOPT_TIMEOUT,
          CURLINFO_HTTP_CODE,
          CURLINFO_HEADER_SIZE;

/**
 * Default HTTP client that using cURL for requests
 */
class HttpClientCurl implements ClientInterface
{

    use HttpClientTrait;

    /**
     * Factory for generate PSR responses
     * @var ResponseFactoryInterface
     */
    private ResponseFactoryInterface $responseFactory;

    /**
     * Fake ResponseInterface for testing purposes
     * @var ResponseInterface|null
     */
    private ?ResponseInterface $fakeResponse = null;

    /**
     * HttpClientCurl constructor.
     *
     * @param ResponseFactoryInterface $responseFactory The response factory implementation.
     * @throws ClientException If the PHP curl extension is not installed.
     */
    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
        if (!extension_loaded('curl')) {
            throw new ClientException('Please install PHP curl extension');
        }
    }

    /**
     * Send a PSR-7 request using cURL and return the response.
     *
     * @param RequestInterface $request The PSR-7 request to send.
     * @return ResponseInterface The PSR-7 response received.
     * @throws NetworkException If sending the request fails.
     * @throws ClientException If failed to retrieve HTTP code or header size.
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

        $curl = curl_init((string) $uri);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->formatHeaders($headers));
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, $this->followLocation);
        curl_setopt($curl, CURLOPT_MAXREDIRS, $this->maxRedirects);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);

        $curlResponse = curl_exec($curl);
        if ($curlResponse === false) {
            throw new NetworkException('Failed to send request: ' . curl_error($curl), $request);
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($httpCode === false) {
            curl_close($curl);
            throw new ClientException('Failed to retrieve HTTP code');
        }

        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        if ($headerSize === false) {
            curl_close($curl);
            throw new ClientException('Failed to retrieve header size');
        }

        $responseHeaders = [];
        $responseBody = '';
        if (is_string($curlResponse)) {
            $responseHeaders = $this->parseResponseHeaders(explode("\r\n", substr($curlResponse, 0, $headerSize)));
            $responseBody = substr($curlResponse, $headerSize);
        }

        curl_close($curl);

        $response = $this->responseFactory->createResponse();
        foreach ($responseHeaders as $headerKey => $headerValue) {
            foreach ($headerValue as $value) {
                $response = $response->withAddedHeader($headerKey, $value);
            }
        }

        if (!empty($responseBody)) {
            $response->getBody()->write($responseBody);
        }

        return $response;
    }

    /**
     * Format headers array into an array of formatted headers.
     *
     * @param array $headers The headers array.
     * @return array The formatted headers array.
     */
    private function formatHeaders(array $headers): array
    {
        $formattedHeaders = [];
        foreach ($headers as $name => $values) {
            foreach ($values as $value) {
                $formattedName = trim($name);
                $formattedValue = trim($value);
                $formattedHeaders[] = "$formattedName: $formattedValue";
            }
        }
        return $formattedHeaders;
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
