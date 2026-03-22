<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\Google\GoogleTasksConnectionPurgeService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'hasGoogleTasksConnection' => $request->user()->hasGoogleTasksConnection(),
            'googleTasksPollIntervalMs' => (int) config('google-tasks.poll_interval_ms'),
            'googleTasksMaxBackoffMs' => (int) config('google-tasks.max_backoff_ms'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        app(GoogleTasksConnectionPurgeService::class)->forgetCachedToken($user);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Disconnect Google (Tasks) — clears tokens, cached access token, and semantic index rows.
     */
    public function disconnectGoogle(Request $request, GoogleTasksConnectionPurgeService $purge): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        if (! $user->hasGoogleTasksConnection()) {
            return Redirect::route('profile.edit')->withErrors([
                'google' => __('profile.google_not_connected'),
            ]);
        }

        $purge->disconnect($user);

        return Redirect::route('profile.edit')->with('status', 'google-disconnected');
    }

    /**
     * Update Tasks-related preferences (undo toast delay, etc.).
     */
    public function updateTasksPreferences(Request $request): RedirectResponse
    {
        $allowed = [3000, 5000, 10000, 15000, 30000];

        $validated = $request->validate([
            'undo_toast_delay_ms' => ['required', 'integer', Rule::in($allowed)],
        ]);

        $request->user()->update([
            'undo_toast_delay_ms' => $validated['undo_toast_delay_ms'],
        ]);

        return Redirect::route('profile.edit');
    }
}
