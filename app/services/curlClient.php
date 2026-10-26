<?php

namespace App\Services;

use App\Exceptions\NetworkExceptions;
use App\Exceptions\HttpExceptions;
use Closure;

class CurlClient
{
    // Mocking / Testing State
    private static bool $isFaking = false;
    private static array $mockResponses = [];
    private static array $recordedRequests = [];

    // Request State
    private array $defaultHeaders = [];
    private array $pendingQuery = [];
    private bool $throwOnHttpError = false;
    private int $maxRetries = 0;
    private int $retryDelayMs = 100;
    private int $timeoutSeconds = 30;
    private mixed $logger = null;

    public function __construct(mixed $logger = null)
    {
        $this->logger = $logger;
    }

    // ==========================================
    // Mocking & Testing Methods (Module 4)
    // ==========================================

    /**
     * Enable fake mode and configure mock responses.
     */
    public static function fake(mixed $setup = null): void
    {
        self::$isFaking = true;
        self::$mockResponses = [];
        self::$recordedRequests = [];

        if ($setup instanceof Response) {
            self::$mockResponses['*'] = $setup;
        } elseif (is_array($setup)) {
            self::$mockResponses = $setup;
        } elseif ($setup instanceof Closure) {
            self::$mockResponses['*'] = $setup;
        } else {
            self::$mockResponses['*'] = self::response(['message' => 'Default fake response OK'], 200);
        }
    }

    /**
     * Disable fake mode and reset recorded requests.
     */
    public static function resetFakes(): void
    {
        self::$isFaking = false;
        self::$mockResponses = [];
        self::$recordedRequests = [];
    }

    /**
     * Retrieve all requests recorded during fake mode.
     */
    public static function recorded(): array
    {
        return self::$recordedRequests;
    }

    /**
     * Helper to quickly construct a Response object for testing.
     */
    public static function response(array|string $body = [], int $status = 200, array $headers = []): Response
    {
        $encodedBody = is_array($body) ? json_encode($body) : $body;

        return new Response([
            'statusCode'    => $status,
            'body'          => $encodedBody,
            'executionTime' => 0.001,
            'size'          => strlen($encodedBody),
            'contentType'   => is_array($body) ? 'application/json' : 'text/html',
            'error'         => null,
        ]);
    }

    private function resolveMockResponse(string $method, string $url, array $data, array $headers): Response
    {
        self::$recordedRequests[] = [
            'method'  => strtoupper($method),
            'url'     => $url,
            'data'    => $data,
            'headers' => $headers,
        ];

        foreach (self::$mockResponses as $pattern => $mock) {
            if ($pattern === '*' || $pattern === $url || str_contains($url, $pattern)) {
                if ($mock instanceof Closure) {
                    return $mock($method, $url, $data, $headers);
                }
                if ($mock instanceof Response) {
                    return $mock;
                }
            }
        }

        return self::response(['message' => 'Default fake response'], 200);
    }

    // ==========================================
    // Fluent Configuration Methods (Module 3)
    // ==========================================

    public function withToken(string $token, string $type = 'Bearer'): self
    {
        $this->defaultHeaders['Authorization'] = "{$type} {$token}";
        return $this;
    }

    public function withBasicAuth(string $username, string $password): self
    {
        $credentials = base64_encode("{$username}:{$password}");
        $this->defaultHeaders['Authorization'] = "Basic {$credentials}";
        return $this;
    }

    public function withApiKey(string $key, string $headerName = 'X-API-Key'): self
    {
        $this->defaultHeaders[$headerName] = $key;
        return $this;
    }

    public function withHeaders(array $headers): self
    {
        $this->defaultHeaders = array_merge($this->defaultHeaders, $headers);
        return $this;
    }

    public function acceptJson(): self
    {
        $this->defaultHeaders['Accept'] = 'application/json';
        return $this;
    }

    public function withQuery(array $query): self
    {
        $this->pendingQuery = array_merge($this->pendingQuery, $query);
        return $this;
    }

    public function timeout(int $seconds): self
    {
        $this->timeoutSeconds = max(1, $seconds);
        return $this;
    }

    public function retry(int $times = 3, int $delayMs = 100): self
    {
        $this->maxRetries = max(0, $times);
        $this->retryDelayMs = max(0, $delayMs);
        return $this;
    }

    public function throw(): self
    {
        $this->throwOnHttpError = true;
        return $this;
    }

    public function withLogger(mixed $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    // ==========================================
    // Internal Logging Helpers (Module 4)
    // ==========================================

    private function dispatchLog(string $level, string $message, array $context = []): void
    {
        if ($this->logger === null) {
            return;
        }

        if (method_exists($this->logger, $level)) {
            $this->logger->$level($message, $context);
        } elseif (method_exists($this->logger, 'log')) {
            $this->logger->log($level, $message, $context);
        }
    }

    private function logResponseTelemetry(string $method, string $url, Response $response): void
    {
        $context = [
            'method'         => strtoupper($method),
            'url'            => $url,
            'status_code'    => $response->getStatusCode(),
            'execution_time' => number_format($response->getExecutionTime(), 4) . 's',
            'size_bytes'     => $response->getSize(),
            'content_type'   => $response->getContentType(),
        ];

        $message = "HyperFetch: {$context['method']} {$url} - {$response->getStatusCode()} ({$context['execution_time']})";

        if ($response->isServerError()) {
            $this->dispatchLog('error', $message, $context);
        } elseif ($response->isClientError()) {
            $this->dispatchLog('warning', $message, $context);
        } else {
            $this->dispatchLog('info', $message, $context);
        }
    }

    private function logNetworkFailure(string $method, string $url, string $error, int $errorCode, int $attempt): void
    {
        $this->dispatchLog('error', "HyperFetch Network Failure [Attempt {$attempt}]: {$error}", [
            'method'    => strtoupper($method),
            'url'       => $url,
            'cUrlError' => $error,
            'cUrlErrNo' => $errorCode,
            'attempt'   => $attempt,
        ]);
    }

    // ==========================================
    // Core Request Engine
    // ==========================================

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
        // Intercept if in Fake Mode
        if (self::$isFaking) {
            $response = $this->resolveMockResponse($method, $url, $data, $headers);
            $this->logResponseTelemetry($method, $url, $response);

            if ($this->throwOnHttpError && $response->isFailed()) {
                throw new HttpExceptions($response);
            }

            $this->pendingQuery = [];
            return $response;
        }

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

            // Handle cURL transport errors
            if ($cUrlErrNo !== 0) {
                $this->logNetworkFailure($method, $url, $cUrlError, $cUrlErrNo, $attempts);

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

            $this->logResponseTelemetry($method, $url, $result);

            // Determine if request warrants retry
            $shouldRetryStatus = ($statusCode === 429 || ($statusCode >= 500 && $statusCode <= 599));

            if ($shouldRetryStatus && $attempts < $totalAllowedAttempts) {
                usleep($this->retryDelayMs * 1000);
                continue;
            }

            if ($this->throwOnHttpError && $result->isFailed()) {
                throw new HttpExceptions($result);
            }

            $this->pendingQuery = [];

            return $result;

        } while ($attempts < $totalAllowedAttempts);

        return $result;
    }

    // ==========================================
    // Public HTTP Verbs
    // ==========================================

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