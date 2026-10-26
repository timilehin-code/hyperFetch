<?php

namespace App\Services;

class SimpleFileLogger
{
    private string $filePath;

    public function __construct(string $filePath = __DIR__ . '/../../logs/http.log')
    {
        $this->filePath = $filePath;
        $directory = dirname($this->filePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextString = !empty($context) ? ' ' . json_encode($context) : '';
        $logEntry = sprintf("[%s] %s: %s%s\n", $timestamp, strtoupper($level), $message, $contextString);

        file_put_contents($this->filePath, $logEntry, FILE_APPEND);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }
}