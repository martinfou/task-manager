<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TaskViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_today_view_returns_tasks_due_today_or_overdue(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ], 200),
            'tasks.googleapis.com/tasks/v1/users/@me/lists' => Http::response([
                'items' => [
                    ['id' => 'list-a', 'title' => 'A'],
                ],
            ], 200),
            'tasks.googleapis.com/tasks/v1/lists/list-a/tasks*' => Http::response([
                'items' => [
                    [
                        'id' => 't1',
                        'title' => 'Overdue',
                        'status' => 'needsAction',
                        'due' => now()->subDay()->toIso8601String(),
                    ],
                    [
                        'id' => 't2',
                        'title' => 'No due',
                        'status' => 'needsAction',
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create([
            'google_refresh_token' => 'fake-refresh',
        ]);

        $response = $this->actingAs($user)->getJson(route('tasks.data.views.today'));

        $response->assertOk();
        $response->assertJsonPath('items.0.task.id', 't1');
        $response->assertJsonPath('items.0.taskListId', 'list-a');
    }

    public function test_inbox_view_uses_my_tasks_list_when_present(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ], 200),
            'tasks.googleapis.com/tasks/v1/users/@me/lists' => Http::response([
                'items' => [
                    ['id' => 'other', 'title' => 'Other'],
                    ['id' => 'inbox-id', 'title' => 'My Tasks'],
                ],
            ], 200),
            'tasks.googleapis.com/tasks/v1/lists/inbox-id/tasks*' => Http::response([
                'items' => [
                    ['id' => 'x', 'title' => 'Inbox task', 'status' => 'needsAction'],
                ],
            ], 200),
        ]);

        $user = User::factory()->create([
            'google_refresh_token' => 'fake-refresh',
        ]);

        $response = $this->actingAs($user)->getJson(route('tasks.data.views.inbox'));

        $response->assertOk();
        $response->assertJsonPath('taskList.id', 'inbox-id');
        $response->assertJsonPath('items.0.title', 'Inbox task');
    }
}
