<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleApiErrorResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_lists_returns_structured_error_on_google_500(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ], 200),
            'tasks.googleapis.com/*' => Http::response([
                'error' => ['message' => 'Internal'],
            ], 500),
        ]);

        $user = User::factory()->create([
            'google_refresh_token' => 'fake-refresh',
        ]);

        $response = $this->actingAs($user)->getJson(route('tasks.data.task-lists'));

        $response->assertStatus(500);
        $response->assertJsonPath('code', 'server_error');
        $response->assertJsonStructure(['message', 'code']);
    }

    public function test_task_lists_returns_rate_limit_payload_on_google_429(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ], 200),
            'tasks.googleapis.com/*' => Http::response([], 429, [
                'Retry-After' => '15',
            ]),
        ]);

        $user = User::factory()->create([
            'google_refresh_token' => 'fake-refresh',
        ]);

        $response = $this->actingAs($user)->getJson(route('tasks.data.task-lists'));

        $response->assertStatus(429);
        $response->assertJsonPath('code', 'rate_limit');
        $response->assertJsonPath('retry_after', 15);
    }

    public function test_task_lists_returns_auth_expired_when_token_refresh_fails(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'error' => 'invalid_grant',
            ], 401),
        ]);

        $user = User::factory()->create([
            'google_refresh_token' => 'fake-refresh',
        ]);

        $response = $this->actingAs($user)->getJson(route('tasks.data.task-lists'));

        $response->assertStatus(401);
        $response->assertJsonPath('code', 'auth_expired');
    }
}
