<?php

namespace Tests\Feature;

use App\Models\TaskEmbedding;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_google_disconnect_clears_tokens_embeddings_and_cache(): void
    {
        $user = User::factory()->create([
            'google_id' => 'google-sub-123',
            'google_refresh_token' => 'refresh-token-value',
        ]);
        Cache::put('google_access_token:'.$user->id, 'cached-access', 3600);

        TaskEmbedding::create([
            'user_id' => $user->id,
            'task_list_id' => 'list-1',
            'task_id' => 'task-1',
            'content_hash' => str_repeat('a', 64),
            'dimensions' => 4,
            'embedding' => [0.1, 0.2, 0.3, 0.4],
            'task_list_title' => 'Inbox',
            'title_raw' => 'Hello',
            'notes_raw' => null,
            'status' => 'needsAction',
        ]);

        $response = $this
            ->actingAs($user)
            ->post('/profile/google/disconnect', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertNull($user->google_id);
        $this->assertNull($user->google_refresh_token);
        $this->assertNull($user->google_token_expires_at);
        $this->assertSame(0, TaskEmbedding::where('user_id', $user->id)->count());
        $this->assertNull(Cache::get('google_access_token:'.$user->id));
    }

    public function test_google_disconnect_requires_valid_password(): void
    {
        $user = User::factory()->create([
            'google_refresh_token' => 'refresh-token-value',
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->post('/profile/google/disconnect', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        $this->assertTrue($user->fresh()->hasGoogleTasksConnection());
    }

    public function test_google_disconnect_when_not_connected_returns_error(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->post('/profile/google/disconnect', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasErrors('google')
            ->assertRedirect('/profile');
    }
}
