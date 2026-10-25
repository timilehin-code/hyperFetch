<?php

namespace App\Services;

use App\Exceptions\NetworkExceptions;
use App\Exceptions\HttpExceptions;

class CurlClient
{

    private array $defaultHeaders = [];
    private bool $throwOnHttpError = false;

    private int $maxRetries = 0;
    private int $retryDelayMs = 200;

    /**
     * Configure automatic retries for transient network/server failures.
     *
     * @param int $times Number of retry attempts (excluding the initial request)
     * @param int $delayMs Pause duration in milliseconds between attempts
     */
    public function retry(int $times = 3, int $delayMs = 100): self
    {
        $this->maxRetries = max(0, $times);
        $this->retryDelayMs = max(0, $delayMs);
        return $this;
    }
    /**
     * Set a Bearer or custom token authorization header.
     */
    public function withToken(string $token, string $type = 'Bearer'): self
    {
        $this->defaultHeaders['Authorization'] = "{$type} {$token}";
        return $this;
    }

    /**
     * Set Basic HTTP Authentication credentials.
     */
    public function withBasicAuth(string $username, string $password): self
    {
        $credentials = base64_encode("{$username}:{$password}");
        $this->defaultHeaders['Authorization'] = "Basic {$credentials}";
        return $this;
    }

    /**
     * Set a custom API Key header.
     */
    public function withApiKey(string $key, string $headerName = 'X-API-Key'): self
    {
        $this->defaultHeaders[$headerName] = $key;
        return $this;
    }

    /**
     * Enable automatic throwing of HttpException on 4xx/5xx responses.
     */
    public function throw(): self
    {
        $this->throwOnHttpError = true;
        return $this;
    }

    /**
     * Converts key-value arrays into cURL header strings.
     * e.g. ['Accept' => 'application/json'] -> ['Accept: application/json']
     */
    private function formatHeaders(array $headers): array
    {
        $formatted = [];

        foreach ($headers as $key => $value) {
            if (is_string($key)) {
                $formatted[] = "{$key}: {$value}";
            } else {
                $formatted[] = $value; // Keeps indexed strings intact
            }
        }

        return $formatted;
    }

    private function request(
        string $method,
        string $url,
        array $data = [],
        array $headers = [],
        array $customOptions = []
    ): Response {
        $attempts = 0;
        $totalAllowedAttempts = 1 + $this->maxRetries;

        do {
            $attempts++;
            $cUrlInit = curl_init($url);

            $defaultOptions = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => strtoupper($method),
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_FOLLOWLOCATION => true,
            ];

            $mergedHeaders = array_merge($this->defaultHeaders, $headers);
            $formattedHeaders = $this->formatHeaders($mergedHeaders);

            if (!empty($data)) {
                $defaultOptions[CURLOPT_POSTFIELDS] = json_encode($data);
                $formattedHeaders[] = 'Content-Type: application/json';
            }

            $defaultOptions[CURLOPT_HTTPHEADER] = $formattedHeaders;
            $finalOptions = $customOptions + $defaultOptions;

            curl_setopt_array($cUrlInit, $finalOptions);

            $response    = curl_exec($cUrlInit);
            $statusCode  = curl_getinfo($cUrlInit, CURLINFO_HTTP_CODE);
            $totalTime   = curl_getinfo($cUrlInit, CURLINFO_TOTAL_TIME);
            $size        = curl_getinfo($cUrlInit, CURLINFO_SIZE_DOWNLOAD);
            $contentType = curl_getinfo($cUrlInit, CURLINFO_CONTENT_TYPE);
            $cUrlError   = curl_error($cUrlInit);
            $cUrlErrNo   = curl_errno($cUrlInit);

            // 1. Handle cURL network-level failures
            if ($cUrlErrNo !== 0) {
                if ($attempts < $totalAllowedAttempts) {
                    usleep($this->retryDelayMs * 1000);
                    continue;
                }
                throw new NetworkExceptions($cUrlError, $cUrlErrNo);
            }

            $result = new Response([
                'statusCode'    => $statusCode,
                'body'          => $response ?: '',
                'executionTime' => $totalTime,
                'size'          => $size,
                'contentType'   => $contentType,
                'error'         => $cUrlError ?: null,
            ]);

            // 2. Check if HTTP status code warrants a retry (5xx server errors or 429 rate limit)
            $shouldRetryStatus = ($statusCode === 429 || ($statusCode >= 500 && $statusCode <= 599));

            if ($shouldRetryStatus && $attempts < $totalAllowedAttempts) {
                usleep($this->retryDelayMs * 1000);
                continue;
            }

            // 3. Throw HttpException if configured and request ultimately failed
            if ($this->throwOnHttpError && $result->isFailed()) {
                throw new HttpExceptions($result);
            }

            return $result;
        } while ($attempts < $totalAllowedAttempts);

        // Fallback return if loop terminates
        return $result;
    }

    public function get(string $url, array $query = [], array $headers = [], array $options = []): Response
    {
        if (!empty($query)) {
            $queryString = http_build_query($query);
            $separator   = str_contains($url, '?') ? '&' : '?';
            $url        .= $separator . $queryString;
        }

        return $this->request('GET', $url, [], $headers, $options);
    }

    public function post(string $url, array $data = [], array $headers = [], array $options = []): Response
    {
        return $this->request('POST', $url, $data, $headers, $options);
    }

    public function put(string $url, array $data = [], array $headers = [], array $options = []): Response
    {
        return $this->request('PUT', $url, $data, $headers, $options);
    }

    public function delete(string $url, array $headers = [], array $options = []): Response
    {
        return $this->request('DELETE', $url, [], $headers, $options);
    }
}
