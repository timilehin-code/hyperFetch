<?php

namespace App\Services;

class CurlClient
{

    private function request(string $method, string $url, array $data = [], array $headers = []): array
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

        return [
            'statusCode'    => $statusCode,
            'body'          => $response,
            'executionTime' => $totalTime,
            'size'          => $size,
            'contentType'   => $contentType,
            'error'         => $error ?: null,
        ];
    }

    public function get(string $url, array $headers = [])
    {

        return $this->request("GET", $url, $headers);
    }


    public function post(string $url, array $data = [], array $headers = []): array
    {
        return $this->request("POST", $url, $data, $headers);
    }

    public function put(string $url, array $data = [], $headers = [])
    {
        return $this->request("PUT", $url, $data, $headers);
    }

    public function Delete(string $url,  $headers = [])
    {
        return $this->request("DELETE", $url, $headers);
    }
}
