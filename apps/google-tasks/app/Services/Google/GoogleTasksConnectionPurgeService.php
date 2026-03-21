<?php

namespace App\Services\Google;

use App\Models\TaskEmbedding;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GoogleTasksConnectionPurgeService
{
    public function __construct(
        private readonly GoogleOAuthTokenService $tokens,
    ) {}

    /**
     * Remove cached OAuth access token, semantic index rows, and Google fields on the user.
     */
    public function disconnect(User $user): void
    {
        DB::transaction(function () use ($user): void {
            TaskEmbedding::query()->where('user_id', $user->id)->delete();
            $this->tokens->forgetCachedAccessToken($user);
            $user->forceFill([
                'google_id' => null,
                'google_refresh_token' => null,
                'google_token_expires_at' => null,
            ])->save();
        });
    }

    /**
     * Clear cached access token only (e.g. before account deletion; embeddings cascade on user delete).
     */
    public function forgetCachedToken(User $user): void
    {
        $this->tokens->forgetCachedAccessToken($user);
    }
}
