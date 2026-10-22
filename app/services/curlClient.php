<?php

namespace App\Services;

class CurlClient
{

    public function get(string $url, array $headers = [])
    {

        $cUrlInit = curl_init($url);

        curl_setopt_array($cUrlInit, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

    
        $response =  curl_exec($cUrlInit);


        // 4. Capture metadata & handle errors
        $statusCode = curl_getinfo($cUrlInit, CURLINFO_HTTP_CODE);
        $totalTime  = curl_getinfo($cUrlInit, CURLINFO_TOTAL_TIME);
        $error      = curl_error($cUrlInit);

        // 5. Close resource handle
        curl_close($cUrlInit);


        $result =  [
            'statusCode'    => $statusCode,
            'body'          => $response,
            'executionTime' => $totalTime,
            'error'         => $error ?: null,
        ];

        return $result;
    }
}
