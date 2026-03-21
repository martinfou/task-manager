<?php

namespace App\Services\Google;

use RuntimeException;

class GoogleTasksRateLimitedException extends RuntimeException
{
    public function __construct(
        public readonly ?int $retryAfterSeconds,
    ) {
        parent::__construct('Google Tasks API rate limited (429).', 429);
    }
}
