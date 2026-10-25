<?php

namespace App\Services;

use App\Exceptions\NetworkException;
use App\Exceptions\HttpException;
use App\Exceptions\HttpExceptions;
use App\Exceptions\NetworkExceptions;

class CurlClient
{
    private array $defaultHeaders = [];
    private array $pendingQuery = [];
    private bool $throwOnHttpError = false;
    private int $maxRetries = 0;
    private int $retryDelayMs = 100;
    private int $timeoutSeconds = 30;

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
     * Set multiple headers at once.
     */
    public function withHeaders(array $headers): self
    {
        $this->defaultHeaders = array_merge($this->defaultHeaders, $headers);
        return $this;
    }

    /**
     * Set default Accept header to JSON.
     */
    public function acceptJson(): self
    {
        $this->defaultHeaders['Accept'] = 'application/json';
        return $this;
    }

    /**
     * Set query parameters fluently.
     */
    public function withQuery(array $query): self
    {
        $this->pendingQuery = array_merge($this->pendingQuery, $query);
        return $this;
    }

    /**
     * Set custom request timeout in seconds.
     */
    public function timeout(int $seconds): self
    {
        $this->timeoutSeconds = max(1, $seconds);
        return $this;
    }

    /**
     * Configure automatic retries for transient failures.
     */
    public function retry(int $times = 3, int $delayMs = 100): self
    {
        $this->maxRetries = max(0, $times);
        $this->retryDelayMs = max(0, $delayMs);
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

    private function formatHeaders(array $headers): array
    {
        $formatted = [];
        foreach ($headers as $key => $value) {
            if (is_string($key)) {
                $formatted[] = "{$key}: {$value}";
            } else {
                $formatted[] = $value;
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
                CURLOPT_TIMEOUT        => $this->timeoutSeconds,
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

            $shouldRetryStatus = ($statusCode === 429 || ($statusCode >= 500 && $statusCode <= 599));

            if ($shouldRetryStatus && $attempts < $totalAllowedAttempts) {
                usleep($this->retryDelayMs * 1000);
                continue;
            }

            if ($this->throwOnHttpError && $result->isFailed()) {
                throw new HttpExceptions($result);
            }

            // Reset pending state for subsequent requests on the same instance
            $this->pendingQuery = [];

            return $result;

        } while ($attempts < $totalAllowedAttempts);

        return $result;
    }

    public function get(string $url, array $query = [], array $headers = [], array $options = []): Response
    {
        $mergedQuery = array_merge($this->pendingQuery, $query);

        if (!empty($mergedQuery)) {
            $queryString = http_build_query($mergedQuery);
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