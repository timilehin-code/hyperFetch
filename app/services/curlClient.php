<?php

namespace App\Services;

class CurlClient
{
    private function request(string $method, string $url, array $data = [], array $headers = []): Response
    {
        $cUrlInit = curl_init($url);

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
        ];

        if (!empty($data)) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
            $headers[] = 'Content-Type: application/json';
        }

        $options[CURLOPT_HTTPHEADER] = $headers;

        curl_setopt_array($cUrlInit, $options);

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

    public function get(string $url, array $headers = []): Response
    {
        return $this->request("GET", $url, [], $headers);
    }

    public function post(string $url, array $data = [], array $headers = []): Response
    {
        return $this->request("POST", $url, $data, $headers);
    }

    public function put(string $url, array $data = [], array $headers = []): Response
    {
        return $this->request("PUT", $url, $data, $headers);
    }

    public function delete(string $url, array $headers = []): Response
    {
        return $this->request("DELETE", $url, [], $headers);
    }
}