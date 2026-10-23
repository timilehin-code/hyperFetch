<?php

namespace App\Controllers;

use App\Services\CurlClient;

class HomeController
{
    public function index(): void
    {
        $client = new CurlClient();

        $response = $client->post('https://httpbin.org/post', [
            'name' => 'Timilehin',
            'app'  => 'HyperFetch'
        ]);

        $data = [
            'pageTitle' => 'HyperFetch POST Test Dashboard',
            'response'  => $response
        ];

        extract($data);
        require_once dirname(__DIR__) . '/Views/home.php';
    }
}
