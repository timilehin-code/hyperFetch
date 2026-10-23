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
        $size = curl_getinfo($cUrlInit, CURLINFO_SIZE_DOWNLOAD);
        $contentType = curl_getinfo($cUrlInit, CURLINFO_CONTENT_TYPE);
        $error      = curl_error($cUrlInit);



        $result =  [
            'statusCode'    => $statusCode,
            'body'          => $response,
            'executionTime' => $totalTime,
            'size' => $size,
            'contentType' => $contentType,
            'error'         => $error ?: null,
        ];

        return $result;
    }


    public function Post(string $cUrlInit, array $data, $headers = [])
    {
        $encodedData =   json_encode($data);

        $headers[] = 'content-type: application/json';

        $cUrlInit = curl_init($cUrlInit);

        curl_setopt_array($cUrlInit, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $encodedData,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $response = curl_exec($cUrlInit);

        $statusCode  = curl_getinfo($cUrlInit, CURLINFO_HTTP_CODE);
        $totalTime   = curl_getinfo($cUrlInit, CURLINFO_TOTAL_TIME);
        $size        = curl_getinfo($cUrlInit, CURLINFO_SIZE_DOWNLOAD);
        $contentType = curl_getinfo($cUrlInit, CURLINFO_CONTENT_TYPE);
        $error       = curl_error($cUrlInit);

        // 7. Return standard result payload
        return [
            'statusCode'    => $statusCode,
            'body'          => $response,
            'executionTime' => $totalTime,
            'size'          => $size,
            'contentType'   => $contentType,
            'error'         => $error ?: null,
        ];
    }

    
}
