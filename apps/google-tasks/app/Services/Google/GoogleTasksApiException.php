<?php

namespace App\Services\Google;

use RuntimeException;
use Throwable;

class GoogleTasksApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly string $errorCode = 'generic',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }
}
