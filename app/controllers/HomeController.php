<?php

namespace App\Controllers;

use App\Services\CurlClient;

class HomeController
{

    public function index()
    {

        $Client = new CurlClient();
        $response = $Client->get("https://httpbin.org/get");
        $data = [
            'Title' => "Home page",
            'status' => "Engine is ready"
        ];
        extract($data);

        require_once dirname(__DIR__) . '/Views/home.php';
    }
}
