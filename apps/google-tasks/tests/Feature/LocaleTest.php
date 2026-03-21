<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_set_locale_cookie(): void
    {
        $response = $this->from('/login')->post('/locale', ['locale' => 'fr']);

        $response->assertRedirect('/login');
        $response->assertCookie('locale', 'fr');
    }

    public function test_authenticated_user_locale_is_persisted(): void
    {
        $user = User::factory()->create(['locale' => null]);

        $response = $this->actingAs($user)->from('/dashboard')->post('/locale', ['locale' => 'fr']);

        $response->assertRedirect('/dashboard');
        $user->refresh();
        $this->assertSame('fr', $user->locale);
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $this->post('/locale', ['locale' => 'de'])->assertSessionHasErrors('locale');
    }
}
