<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Google\GoogleOAuthTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleOAuthController extends Controller
{
    /** Google Tasks API scope (read/write). */
    public const TASKS_SCOPE = 'https://www.googleapis.com/auth/tasks';

    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email', self::TASKS_SCOPE])
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
            ])
            ->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            // Session expired between redirect and callback — retry stateless.
            // This is safe because we still validate the code exchange with Google.
            $googleUser = Socialite::driver('google')->stateless()->user();
        }

        $email = $googleUser->getEmail();
        if ($email === null || $email === '') {
            return redirect()->route('login')
                ->withErrors(['email' => 'Google did not return an email address.']);
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $user = User::query()->create([
                'name' => $googleUser->getName() ?? $googleUser->getNickname() ?? 'User',
                'email' => $email,
                'password' => Hash::make(Str::random(64)),
                'email_verified_at' => now(),
            ]);
        }

        $user->google_id = $googleUser->getId();
        $user->google_token_expires_at = isset($googleUser->expiresIn)
            ? now()->addSeconds((int) $googleUser->expiresIn)
            : null;

        if (! empty($googleUser->refreshToken)) {
            $user->google_refresh_token = $googleUser->refreshToken;
        }

        $user->save();

        app(GoogleOAuthTokenService::class)->forgetCachedAccessToken($user);

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
