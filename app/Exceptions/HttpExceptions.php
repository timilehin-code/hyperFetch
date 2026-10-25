<?php

namespace App\Exceptions;

use App\Services\Response;

class HttpExceptions extends HyperFetchExceptions
{
    public function __construct(private Response $response)
    {
        parent::__construct(
            "HTTP request failed with status code {$response->getStatusCode()}",
            $response->getStatusCode()
        );
    }
    public function getResponse(): Response
    {
        return $this->response;
    }
}
