<?php

namespace App\Controllers;

use App\Services\CurlClient;
use App\Services\SimpleFileLogger;

class HomeController
{
    public function index(): void
    {
        $logger = new SimpleFileLogger();
        
        // Pass logger instance via constructor or withLogger()
        $client = (new CurlClient($logger))
            ->withToken('sk_live_12345')
            ->timeout(10);

        // Execute GET request
        $response = $client->get('https://httpbin.org/get');

        $data = [
            'pageTitle' => 'PSR-3 Logging Active',
            'response'  => $response
        ];

        extract($data);
        require_once dirname(__DIR__) . '/Views/home.php';
    }
}