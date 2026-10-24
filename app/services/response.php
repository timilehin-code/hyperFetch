<?php

namespace App\Services;

class Response
{

    public int $statusCode;
    public string $body;
    public float $executionTime;
    public int $size;
    public ?string $contentType;
    private ?string $error;
    private mixed $parsedJson = null;


    public function __construct(array $responseData)
    {
        $this->statusCode    = $responseData['statusCode'] ?? 0;
        $this->body          = $responseData['body'] ?? '';
        $this->executionTime = $responseData['executionTime'] ?? 0.0;
        $this->size          = $responseData['size'] ?? 0;
        $this->contentType   = $responseData['contentType'] ?? null;
        $this->error         = $responseData['error'] ?? null;

        $this->parseJson();
    }

    private function parseJson()
    {
        if (empty($this->body)) {
            return;
        }

        $decoded = json_decode($this->body, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            $this->parsedJson = $decoded;
        }
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function json(mixed $default = null)
    {
        return $this->parsedJson ?? $default;
    }

    public function isJson(): bool
    {
        return $this->parsedJson !== null;
    }

    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    // Status Code Helpers
    public function isSuccess(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function isRedirect(): bool
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    public function isServerError(): bool
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    public function isFailed(): bool
    {
        return $this->isClientError() || $this->isServerError() || $this->error !== null;
    }
}
