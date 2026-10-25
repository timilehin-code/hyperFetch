<?php

namespace App\Controllers;

use App\Services\CurlClient;
use App\Exceptions\NetworkExceptions;
use App\Exceptions\HttpExceptions;

class HomeController
{
    public function index(): void
    {
        $client = new CurlClient();

        try {
            // Attempt request up to 3 times with a 200ms pause between tries
            $response = $client->withToken('sk_live_998877')
                ->acceptJson()
                ->timeout(10)
                ->withQuery(['category' => 'tech', 'limit' => 5])
                ->retry(2, 150)
                ->get('https://httpbin.org/get');

            $data = [
                'pageTitle' => 'Retry Test Result',
                'response'  => $response
            ];
        } catch (HttpExceptions $e) {
            echo "Failed after all retries. Status: " . $e->getResponse()->getStatusCode();
            return;
        } catch (NetworkExceptions $e) {
            echo "Network issue after all retries: " . $e->getMessage();
            return;
        }

        extract($data);
        require_once dirname(__DIR__) . '/Views/home.php';
    }
}
