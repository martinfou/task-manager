<?php

namespace App\Services\Google;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleOAuthTokenService
{
    public function forgetCachedAccessToken(User $user): void
    {
        Cache::forget($this->cacheKey($user));
    }

    public function getAccessToken(User $user): string
    {
        if (! $user->google_refresh_token) {
            throw new GoogleTasksApiException(
                GoogleTasksErrorCode::AuthExpired->userMessage(),
                401,
                GoogleTasksErrorCode::AuthExpired->value,
            );
        }

        $cached = Cache::get($this->cacheKey($user));
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        return $this->refreshAccessToken($user);
    }

    private function cacheKey(User $user): string
    {
        return 'google_access_token:'.$user->getKey();
    }

    private function refreshAccessToken(User $user): string
    {
        $response = Http::timeout(10)->connectTimeout(5)->asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $user->google_refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            Cache::forget($this->cacheKey($user));
            Log::warning('google_oauth_token_refresh_failed', [
                'user_id' => $user->getKey(),
                'status' => $response->status(),
            ]);

            throw new GoogleTasksApiException(
                GoogleTasksErrorCode::AuthExpired->userMessage(),
                401,
                GoogleTasksErrorCode::AuthExpired->value,
            );
        }

        $data = $response->json();
        $accessToken = $data['access_token'] ?? null;
        if (! is_string($accessToken) || $accessToken === '') {
            Cache::forget($this->cacheKey($user));
            Log::warning('google_oauth_token_response_invalid', [
                'user_id' => $user->getKey(),
            ]);

            throw new GoogleTasksApiException(
                GoogleTasksErrorCode::AuthExpired->userMessage(),
                502,
                GoogleTasksErrorCode::AuthExpired->value,
            );
        }

        $expiresIn = isset($data['expires_in']) ? (int) $data['expires_in'] : 3600;
        $user->google_token_expires_at = now()->addSeconds($expiresIn);
        $user->save();

        $ttlSeconds = max(60, $expiresIn - 120);
        Cache::put($this->cacheKey($user), $accessToken, $ttlSeconds);

        return $accessToken;
    }
}
