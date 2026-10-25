<?php

namespace App\Exceptions;

use Throwable;
use Override;

class NetworkExceptions extends HyperFetchExceptions
{

    public function __construct(string $cUrlError, int $cUrlErrorCode = 0)
    {
        parent::__construct("Network/cURL connection failed: {$cUrlError}", $cUrlErrorCode);
    }
}
