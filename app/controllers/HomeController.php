<?php

namespace App\Controllers;

use App\Services\CurlClient;

class HomeController
{
    public function index(): void
    {
        $client = new CurlClient();
        $response = $client->get(
            'https://httpbin.org/headers',
            ['ref' => 'dashboard'],                           
            [                                                 
                'Authorization' => 'Bearer secret_token_123',
                'User-Agent'    => 'HyperFetch/1.0 Engine'
            ],
            [                                                
                CURLOPT_TIMEOUT => 15                         
            ]
        );
        $data = [
            'pageTitle'  => 'HyperFetch JSON Test Dashboard',
            'response'   => $response,
            'parsedData' => $response->isSuccess() ? $response->json() : null
        ];

        extract($data);
        require_once dirname(__DIR__) . '/Views/home.php';
    }
}
