<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SemanticSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_semantic_search_returns_422_when_not_configured(): void
    {
        config(['google-tasks.semantic_search.enabled' => false]);

        $user = User::factory()->create([
            'google_refresh_token' => 'fake-refresh',
        ]);

        $response = $this->actingAs($user)->getJson(route('tasks.data.search', [
            'q' => 'hello',
            'mode' => 'semantic',
        ]));

        $response->assertStatus(422);
        $response->assertJsonPath('code', 'semantic_unavailable');
    }

    public function test_keyword_search_includes_semantic_available_flag(): void
    {
        config(['google-tasks.semantic_search.enabled' => false]);

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ], 200),
            'tasks.googleapis.com/*' => Http::response([
                'items' => [],
            ], 200),
        ]);

        $user = User::factory()->create([
            'google_refresh_token' => 'fake-refresh',
        ]);

        $response = $this->actingAs($user)->getJson(route('tasks.data.search', [
            'q' => 'ab',
        ]));

        $response->assertOk();
        $response->assertJsonPath('mode', 'keyword');
        $response->assertJsonStructure(['semantic_available']);
    }

    public function test_reindex_returns_422_when_unconfigured(): void
    {
        config(['google-tasks.semantic_search.enabled' => false]);

        $user = User::factory()->create([
            'google_refresh_token' => 'fake-refresh',
        ]);

        $response = $this->actingAs($user)->postJson(route('tasks.data.search.reindex'));

        $response->assertStatus(422);
        $response->assertJsonPath('code', 'semantic_unavailable');
    }
}
