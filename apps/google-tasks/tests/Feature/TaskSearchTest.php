<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TaskSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_requires_google_connection(): void
    {
        $user = User::factory()->create([
            'google_refresh_token' => null,
        ]);

        $response = $this->actingAs($user)->getJson(route('tasks.data.search', ['q' => 'ab']));

        $response->assertForbidden();
    }

    public function test_search_requires_minimum_query_length(): void
    {
        $user = User::factory()->create([
            'google_refresh_token' => 'fake',
        ]);

        $response = $this->actingAs($user)->getJson(route('tasks.data.search', ['q' => 'a']));

        $response->assertUnprocessable();
    }

    public function test_search_returns_matches_with_snippets(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ], 200),
            'tasks.googleapis.com/tasks/v1/users/@me/lists' => Http::response([
                'items' => [
                    ['id' => 'list-1', 'title' => 'Work'],
                ],
            ], 200),
            'tasks.googleapis.com/tasks/v1/lists/list-1/tasks*' => Http::response([
                'items' => [
                    [
                        'id' => 'task-1',
                        'title' => 'Buy milk',
                        'notes' => 'Get organic milk at the store',
                        'status' => 'needsAction',
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create([
            'google_refresh_token' => 'fake-refresh',
        ]);

        $response = $this->actingAs($user)->getJson(route('tasks.data.search', [
            'q' => 'milk',
        ]));

        $response->assertOk();
        $response->assertJsonPath('items.0.task.title', 'Buy milk');
        $response->assertJsonPath('items.0.taskListTitle', 'Work');
        $response->assertJsonPath('items.0.taskListId', 'list-1');
        $this->assertNotNull($response->json('items.0.snippet'));
    }
}
