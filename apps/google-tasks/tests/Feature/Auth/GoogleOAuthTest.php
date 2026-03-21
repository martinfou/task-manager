<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleOAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_sends_user_to_google(): void
    {
        Socialite::shouldReceive('driver->scopes->with->redirect')
            ->once()
            ->andReturn(redirect('https://accounts.google.com/o/oauth2/v2/auth'));

        $response = $this->get(route('google.redirect'));

        $response->assertRedirect('https://accounts.google.com/o/oauth2/v2/auth');
    }

    public function test_callback_creates_user_and_logs_in(): void
    {
        $google = new SocialiteUser;
        $google->id = 'google-sub-123';
        $google->email = 'new@example.com';
        $google->name = 'New User';
        $google->token = 'access-token';
        $google->refreshToken = 'refresh-token';
        $google->expiresIn = 3600;

        Socialite::shouldReceive('driver->user')->once()->andReturn($google);

        $response = $this->get(route('google.callback'));

        $response->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();

        $user = User::query()->where('email', 'new@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('google-sub-123', $user->google_id);
        $this->assertSame('refresh-token', $user->google_refresh_token);
    }

    public function test_callback_updates_existing_user_by_email(): void
    {
        $existing = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $google = new SocialiteUser;
        $google->id = 'google-sub-999';
        $google->email = 'existing@example.com';
        $google->name = 'Updated Name';
        $google->token = 'access-token';
        $google->refreshToken = 'refresh-token';
        $google->expiresIn = 3600;

        Socialite::shouldReceive('driver->user')->once()->andReturn($google);

        $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

        $this->assertSame(1, User::query()->count());
        $existing->refresh();
        $this->assertSame('google-sub-999', $existing->google_id);
        $this->assertSame('refresh-token', $existing->google_refresh_token);
    }

    public function test_callback_redirects_to_login_when_email_missing(): void
    {
        $google = new SocialiteUser;
        $google->id = 'x';
        $google->email = '';
        $google->name = 'X';
        $google->token = 't';

        Socialite::shouldReceive('driver->user')->once()->andReturn($google);

        $response = $this->get(route('google.callback'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
