<?php

namespace App\Services;

class CurlClient
{



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
        $cUrlInit = curl_init($url);

        // Standard defaults
        $defaultOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
        ];

        // Format headers into valid "Key: Value" strings
        $formattedHeaders = $this->formatHeaders($headers);

        if (!empty($data)) {
            $defaultOptions[CURLOPT_POSTFIELDS] = json_encode($data);
            $formattedHeaders[] = 'Content-Type: application/json';
        }

        $defaultOptions[CURLOPT_HTTPHEADER] = $formattedHeaders;

        // Custom options override defaults if keys collide
        $finalOptions = $customOptions + $defaultOptions;

        curl_setopt_array($cUrlInit, $finalOptions);

        $response    = curl_exec($cUrlInit);
        $statusCode  = curl_getinfo($cUrlInit, CURLINFO_HTTP_CODE);
        $totalTime   = curl_getinfo($cUrlInit, CURLINFO_TOTAL_TIME);
        $size        = curl_getinfo($cUrlInit, CURLINFO_SIZE_DOWNLOAD);
        $contentType = curl_getinfo($cUrlInit, CURLINFO_CONTENT_TYPE);
        $error       = curl_error($cUrlInit);

        $result = [
            'statusCode'    => $statusCode,
            'body'          => $response ?: '',
            'executionTime' => $totalTime,
            'size'          => $size,
            'contentType'   => $contentType,
            'error'         => $error ?: null,
        ];

        return new Response($result);
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