<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TasksSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_tasks_index_page_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('tasks.index'));

        $response->assertOk();
    }

    public function test_task_lists_returns_403_without_google_connection(): void
    {
        $user = User::factory()->create([
            'google_refresh_token' => null,
        ]);

        $response = $this->actingAs($user)->getJson(route('tasks.data.task-lists'));

        $response->assertForbidden();
    }

    public function test_task_lists_returns_json_from_google_api(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ], 200),
            'tasks.googleapis.com/*' => Http::response([
                'items' => [
                    ['id' => 'list-1', 'title' => 'My List'],
                ],
            ], 200),
        ]);

        $user = User::factory()->create([
            'google_refresh_token' => 'fake-refresh',
        ]);

        $response = $this->actingAs($user)->getJson(route('tasks.data.task-lists'));

        $response->assertOk();
        $response->assertJsonPath('items.0.id', 'list-1');
    }

    public function test_store_task_proxies_to_google_api(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ], 200),
            'tasks.googleapis.com/tasks/v1/lists/list-1/tasks' => Http::response([
                'id' => 'task-1',
                'title' => '[P2] Hello',
                'status' => 'needsAction',
            ], 200),
        ]);

        $user = User::factory()->create([
            'google_refresh_token' => 'fake-refresh',
        ]);

        $response = $this->actingAs($user)->postJson(route('tasks.data.tasks.store', [
            'taskList' => 'list-1',
        ]), [
            'title' => 'Hello',
            'priority' => 'p2',
        ]);

        $response->assertOk();
        $response->assertJsonPath('id', 'task-1');
        $response->assertJsonPath('title', 'Hello');
        $response->assertJsonPath('priority', 'p2');
        Http::assertSent(function (HttpRequest $request): bool {
            if (! str_contains($request->url(), '/lists/list-1/tasks')) {
                return true;
            }

            return $request['title'] === '[P2] Hello';
        });
    }

    public function test_move_task_proxies_to_google_api(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ], 200),
            'tasks.googleapis.com/tasks/v1/lists/list-1/tasks/task-1/move*' => Http::response([
                'id' => 'task-1',
                'title' => 'Moved',
                'status' => 'needsAction',
            ], 200),
        ]);

        $user = User::factory()->create([
            'google_refresh_token' => 'fake-refresh',
        ]);

        $response = $this->actingAs($user)->postJson(route('tasks.data.tasks.move', [
            'taskList' => 'list-1',
            'task' => 'task-1',
        ]), [
            'destinationTasklist' => 'list-2',
        ]);

        $response->assertOk();
        $response->assertJsonPath('title', 'Moved');
        Http::assertSent(function (HttpRequest $request): bool {
            if (! str_contains($request->url(), '/lists/list-1/tasks/task-1/move')) {
                return true;
            }

            return str_contains($request->url(), 'destinationTasklist=list-2');
        });
    }

    public function test_store_task_sends_notes_to_google_api(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ], 200),
            'tasks.googleapis.com/tasks/v1/lists/list-1/tasks' => Http::response([
                'id' => 'task-notes',
                'title' => 'Doc',
                'notes' => 'https://example.com/a',
                'status' => 'needsAction',
            ], 200),
        ]);

        $user = User::factory()->create([
            'google_refresh_token' => 'fake-refresh',
        ]);

        $response = $this->actingAs($user)->postJson(route('tasks.data.tasks.store', [
            'taskList' => 'list-1',
        ]), [
            'title' => 'Doc',
            'notes' => 'https://example.com/a',
        ]);

        $response->assertOk();
        $response->assertJsonPath('notes', 'https://example.com/a');
        Http::assertSent(function (HttpRequest $request): bool {
            if (! str_contains($request->url(), '/lists/list-1/tasks')) {
                return true;
            }

            return ($request['notes'] ?? null) === 'https://example.com/a';
        });
    }

    public function test_tasks_endpoint_decodes_encoded_priority_from_google_title(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ], 200),
            'tasks.googleapis.com/tasks/v1/lists/list-1/tasks*' => Http::response([
                'items' => [
                    [
                        'id' => 'task-1',
                        'title' => '[P1] Follow up customer issue',
                        'status' => 'needsAction',
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create([
            'google_refresh_token' => 'fake-refresh',
        ]);

        $response = $this->actingAs($user)->getJson(route('tasks.data.tasks', [
            'taskList' => 'list-1',
        ]));

        $response->assertOk();
        $response->assertJsonPath('items.0.title', 'Follow up customer issue');
        $response->assertJsonPath('items.0.priority', 'p1');
        $response->assertJsonPath('items.0.titleRaw', '[P1] Follow up customer issue');
    }
}
