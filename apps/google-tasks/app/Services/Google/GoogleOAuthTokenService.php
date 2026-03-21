<?php

namespace App\Services\Google;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GoogleOAuthTokenService
{
    public function forgetCachedAccessToken(User $user): void
    {
        Cache::forget($this->cacheKey($user));
    }

    public function getAccessToken(User $user): string
    {
        if (! $user->google_refresh_token) {
            throw new GoogleTasksApiException('No Google refresh token on user.', 401);
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
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $user->google_refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            Cache::forget($this->cacheKey($user));
            throw new GoogleTasksApiException(
                'Failed to refresh Google access token: '.$response->body(),
                $response->status(),
            );
        }

        $data = $response->json();
        $accessToken = $data['access_token'] ?? null;
        if (! is_string($accessToken) || $accessToken === '') {
            Cache::forget($this->cacheKey($user));
            throw new GoogleTasksApiException('Google token response missing access_token.', 502);
        }

        $expiresIn = isset($data['expires_in']) ? (int) $data['expires_in'] : 3600;
        $user->google_token_expires_at = now()->addSeconds($expiresIn);
        $user->save();

        $ttlSeconds = max(60, $expiresIn - 120);
        Cache::put($this->cacheKey($user), $accessToken, $ttlSeconds);

        return $accessToken;
    }
}
