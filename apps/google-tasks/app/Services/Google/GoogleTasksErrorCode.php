<?php

namespace App\Services\Google;

enum GoogleTasksErrorCode: string
{
    case AuthExpired = 'auth_expired';
    case RateLimited = 'rate_limit';
    case ServerError = 'server_error';
    case Network = 'network';
    case BadRequest = 'bad_request';
    case NotFound = 'not_found';
    case Forbidden = 'forbidden';
    case Generic = 'generic';

    public static function fromHttpStatus(int $status): self
    {
        return match (true) {
            $status === 401 => self::AuthExpired,
            $status === 403 => self::Forbidden,
            $status === 404 => self::NotFound,
            $status >= 500 && $status < 600 => self::ServerError,
            $status >= 400 && $status < 500 => self::BadRequest,
            default => self::Generic,
        };
    }

    public function userMessage(): string
    {
        return match ($this) {
            self::AuthExpired => 'Google account access expired. Reconnect Google in Settings.',
            self::RateLimited => 'Google rate limited this request. Try again shortly.',
            self::ServerError => 'Google Tasks is temporarily unavailable. Try again later.',
            self::Network => 'Could not reach Google. Check your connection.',
            self::BadRequest => 'The request could not be completed.',
            self::NotFound => 'The requested resource was not found.',
            self::Forbidden => 'Google denied this request.',
            self::Generic => 'Google Tasks request failed.',
        };
    }
}
