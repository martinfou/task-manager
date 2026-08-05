<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleTasksPaginationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regression test (2026-08-05): GoogleTasksClient::listTasks must follow
     * nextPageToken. Before the fix, a list with >20 items (Google's default
     * page size when maxResults is omitted) was silently truncated, so tasks
     * beyond the first page never appeared in the app even though they existed
     * in Google Tasks (real case: 🏠 Home 82 items — "Tester batterie 12V
     * Spark + Bolt" and "Tester eau piscine + spa" were invisible).
     */
    public function test_list_tasks_follows_next_page_token_and_merges_items(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ], 200),
            // First request returns page 1 with a nextPageToken; the second
            // request (with pageToken) returns page 2 and no further token.
            'tasks.googleapis.com/tasks/v1/lists/list-a/tasks*' => Http::sequence()
                ->push([
                    'items' => [
                        ['id' => 't1', 'title' => 'First page', 'status' => 'needsAction'],
                        ['id' => 't2', 'title' => 'Second item', 'status' => 'needsAction'],
                    ],
                    'nextPageToken' => 'NEXT-TOKEN',
                ], 200)
                ->push([
                    'items' => [
                        ['id' => 't60', 'title' => 'Tester batterie 12V Spark + Bolt', 'status' => 'needsAction'],
                        ['id' => 't61', 'title' => 'Tester eau piscine + spa', 'status' => 'needsAction'],
                    ],
                ], 200),
        ]);

        $user = User::factory()->create([
            'google_refresh_token' => 'fake-refresh',
        ]);

        $response = $this->actingAs($user)->getJson(
            route('tasks.data.tasks', ['taskList' => 'list-a']),
        );

        $response->assertOk();
        $items = $response->json('items');
        $this->assertCount(4, $items);
        $this->assertEquals('t1', $items[0]['id']);
        $this->assertEquals('t60', $items[2]['id']);
        $this->assertEquals('Tester batterie 12V Spark + Bolt', $items[2]['title']);
        $this->assertEquals('Tester eau piscine + spa', $items[3]['title']);

        // The second request must carry the pageToken so Google returns page 2.
        $requests = Http::recorded();
        $taskCalls = $requests->filter(fn ($pair) => str_contains($pair[0]->url(), 'lists/list-a/tasks'))->values();
        $this->assertCount(2, $taskCalls);
        $this->assertStringContainsString('maxResults=100', $taskCalls[0][0]->url());
        $this->assertStringContainsString('pageToken=NEXT-TOKEN', $taskCalls[1][0]->url());
    }
}
