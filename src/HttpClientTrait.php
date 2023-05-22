<?php

declare(strict_types=1);

namespace Core\HttpClient;

trait HttpClientTrait
{

    protected bool $followLocation = true;
    protected int $maxRedirects = 3;
    protected int $timeout = 10;

    /**
     * Set request connection timeout
     * 
     * @param int $timeout Timeout for connection
     * @return self
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * The max number of redirects to follow.
     * 
     * @param int $maxRedirects Default is 3
     * @return self
     */
    public function setMaxRedirects(int $maxRedirects): self
    {
        $this->maxRedirects = $maxRedirects;
        return $this;
    }

    /**
     * Follow Location header redirects.
     * 
     * @param bool $followLocation Default is true
     * @return self
     */
    public function setFollowLocation(bool $followLocation): self
    {
        $this->followLocation = $followLocation;
        return $this;
    }

    /**
     * Parse raw response headers into an associative array.
     *
     * @param array $rawHeaders The raw response headers array.
     * @return array The parsed response headers array.
     */
    protected function parseResponseHeaders(array $rawHeaders): array
    {
        $headers = [];
        foreach ($rawHeaders as $header) {
            $headerParts = explode(':', $header, 2);
            if (count($headerParts) === 2) {
                $name = trim($headerParts[0]);
                $value = trim($headerParts[1]);
                $headers[$name][] = $value;
            }
        }
        return $headers;
    }

}
