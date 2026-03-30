<?php

namespace Tests\Feature;

use App\Models\TaskViewCache;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskViewCachePatchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ── removeTaskFromCaches ──

    public function test_remove_task_from_today_cache(): void
    {
        TaskViewCache::putCache($this->user->id, 'today', [], [
            'items' => [
                ['taskListId' => 'L1', 'taskListTitle' => 'Work', 'task' => ['id' => 'T1', 'title' => 'Buy milk']],
                ['taskListId' => 'L1', 'taskListTitle' => 'Work', 'task' => ['id' => 'T2', 'title' => 'Fix bug']],
            ],
        ]);

        TaskViewCache::removeTaskFromCaches($this->user->id, 'T1');

        $cached = TaskViewCache::getCached($this->user->id, 'today', []);
        $this->assertCount(1, $cached->payload['items']);
        $this->assertEquals('T2', $cached->payload['items'][0]['task']['id']);
        $this->assertNull($cached->stale_at);
    }

    public function test_remove_task_from_inbox_cache(): void
    {
        TaskViewCache::putCache($this->user->id, 'inbox', ['showCompleted' => true], [
            'taskList' => ['id' => 'L1', 'title' => 'My Tasks'],
            'items' => [
                ['id' => 'T1', 'title' => 'Task A'],
                ['id' => 'T2', 'title' => 'Task B'],
            ],
        ]);

        TaskViewCache::removeTaskFromCaches($this->user->id, 'T1');

        $cached = TaskViewCache::getCached($this->user->id, 'inbox', ['showCompleted' => true]);
        $this->assertCount(1, $cached->payload['items']);
        $this->assertEquals('T2', $cached->payload['items'][0]['id']);
    }

    public function test_remove_task_from_all_cache(): void
    {
        TaskViewCache::putCache($this->user->id, 'all', ['showCompleted' => false], [
            'items' => [
                ['taskListId' => 'L1', 'taskListTitle' => 'Work', 'task' => ['id' => 'T1', 'title' => 'A']],
                ['taskListId' => 'L2', 'taskListTitle' => 'Personal', 'task' => ['id' => 'T2', 'title' => 'B']],
                ['taskListId' => 'L1', 'taskListTitle' => 'Work', 'task' => ['id' => 'T3', 'title' => 'C']],
            ],
        ]);

        TaskViewCache::removeTaskFromCaches($this->user->id, 'T2');

        $cached = TaskViewCache::getCached($this->user->id, 'all', ['showCompleted' => false]);
        $this->assertCount(2, $cached->payload['items']);
        $ids = array_column(array_column($cached->payload['items'], 'task'), 'id');
        $this->assertEquals(['T1', 'T3'], $ids);
    }

    public function test_remove_task_from_multiple_views(): void
    {
        TaskViewCache::putCache($this->user->id, 'today', [], [
            'items' => [
                ['taskListId' => 'L1', 'taskListTitle' => 'Work', 'task' => ['id' => 'T1', 'title' => 'X']],
            ],
        ]);
        TaskViewCache::putCache($this->user->id, 'inbox', ['showCompleted' => true], [
            'taskList' => ['id' => 'L1'],
            'items' => [
                ['id' => 'T1', 'title' => 'X'],
                ['id' => 'T2', 'title' => 'Y'],
            ],
        ]);

        TaskViewCache::removeTaskFromCaches($this->user->id, 'T1');

        $today = TaskViewCache::getCached($this->user->id, 'today', []);
        $this->assertCount(0, $today->payload['items']);

        $inbox = TaskViewCache::getCached($this->user->id, 'inbox', ['showCompleted' => true]);
        $this->assertCount(1, $inbox->payload['items']);
        $this->assertEquals('T2', $inbox->payload['items'][0]['id']);
    }

    public function test_remove_nonexistent_task_is_noop(): void
    {
        TaskViewCache::putCache($this->user->id, 'today', [], [
            'items' => [
                ['taskListId' => 'L1', 'taskListTitle' => 'Work', 'task' => ['id' => 'T1', 'title' => 'X']],
            ],
        ]);
        $before = TaskViewCache::getCached($this->user->id, 'today', [])->computed_at;

        TaskViewCache::removeTaskFromCaches($this->user->id, 'NONEXISTENT');

        $cached = TaskViewCache::getCached($this->user->id, 'today', []);
        $this->assertCount(1, $cached->payload['items']);
        // computed_at should not change since nothing was removed
        $this->assertEquals($before->toIso8601String(), $cached->computed_at->toIso8601String());
    }

    public function test_remove_does_not_affect_other_users(): void
    {
        $other = User::factory()->create();
        TaskViewCache::putCache($other->id, 'today', [], [
            'items' => [
                ['taskListId' => 'L1', 'taskListTitle' => 'Work', 'task' => ['id' => 'T1', 'title' => 'X']],
            ],
        ]);

        TaskViewCache::removeTaskFromCaches($this->user->id, 'T1');

        $cached = TaskViewCache::getCached($other->id, 'today', []);
        $this->assertCount(1, $cached->payload['items']);
    }

    // ── upsertTaskInCaches ──

    public function test_upsert_updates_task_in_today_cache(): void
    {
        TaskViewCache::putCache($this->user->id, 'today', [], [
            'items' => [
                ['taskListId' => 'L1', 'taskListTitle' => 'Work', 'task' => ['id' => 'T1', 'title' => 'Old title', 'status' => 'needsAction']],
            ],
        ]);

        TaskViewCache::upsertTaskInCaches($this->user->id, 'T1', ['id' => 'T1', 'title' => 'New title', 'status' => 'needsAction'], 'L1');

        $cached = TaskViewCache::getCached($this->user->id, 'today', []);
        $this->assertEquals('New title', $cached->payload['items'][0]['task']['title']);
        $this->assertNull($cached->stale_at);
    }

    public function test_upsert_updates_task_in_inbox_cache(): void
    {
        TaskViewCache::putCache($this->user->id, 'inbox', ['showCompleted' => true], [
            'taskList' => ['id' => 'L1'],
            'items' => [
                ['id' => 'T1', 'title' => 'Old', 'status' => 'needsAction'],
            ],
        ]);

        TaskViewCache::upsertTaskInCaches($this->user->id, 'T1', ['id' => 'T1', 'title' => 'Updated', 'status' => 'needsAction']);

        $cached = TaskViewCache::getCached($this->user->id, 'inbox', ['showCompleted' => true]);
        $this->assertEquals('Updated', $cached->payload['items'][0]['title']);
    }

    public function test_upsert_completed_task_removed_from_today(): void
    {
        TaskViewCache::putCache($this->user->id, 'today', [], [
            'items' => [
                ['taskListId' => 'L1', 'taskListTitle' => 'Work', 'task' => ['id' => 'T1', 'title' => 'X', 'status' => 'needsAction']],
                ['taskListId' => 'L1', 'taskListTitle' => 'Work', 'task' => ['id' => 'T2', 'title' => 'Y', 'status' => 'needsAction']],
            ],
        ]);

        TaskViewCache::upsertTaskInCaches($this->user->id, 'T1', ['id' => 'T1', 'title' => 'X', 'status' => 'completed'], 'L1');

        $cached = TaskViewCache::getCached($this->user->id, 'today', []);
        $this->assertCount(1, $cached->payload['items']);
        $this->assertEquals('T2', $cached->payload['items'][0]['task']['id']);
    }

    public function test_upsert_does_not_add_task_to_view_where_absent(): void
    {
        TaskViewCache::putCache($this->user->id, 'today', [], [
            'items' => [
                ['taskListId' => 'L1', 'taskListTitle' => 'Work', 'task' => ['id' => 'T1', 'title' => 'X', 'status' => 'needsAction']],
            ],
        ]);

        // T99 doesn't exist in today cache — should not be added
        TaskViewCache::upsertTaskInCaches($this->user->id, 'T99', ['id' => 'T99', 'title' => 'New', 'status' => 'needsAction'], 'L1');

        $cached = TaskViewCache::getCached($this->user->id, 'today', []);
        $this->assertCount(1, $cached->payload['items']);
        $this->assertEquals('T1', $cached->payload['items'][0]['task']['id']);
    }

    // ── Regression test for DEF-002 ──

    public function test_delete_mutation_does_not_return_stale_data(): void
    {
        // Simulate: cache has task T1
        TaskViewCache::putCache($this->user->id, 'today', [], [
            'items' => [
                ['taskListId' => 'L1', 'taskListTitle' => 'Work', 'task' => ['id' => 'T1', 'title' => 'Delete me', 'status' => 'needsAction']],
                ['taskListId' => 'L1', 'taskListTitle' => 'Work', 'task' => ['id' => 'T2', 'title' => 'Keep me', 'status' => 'needsAction']],
            ],
        ]);

        // Simulate: delete mutation surgically removes T1
        TaskViewCache::removeTaskFromCaches($this->user->id, 'T1');

        // Simulate: next request reads cache (should NOT contain T1)
        $cached = TaskViewCache::getCached($this->user->id, 'today', []);
        $this->assertTrue($cached->isFresh());
        $this->assertCount(1, $cached->payload['items']);
        $this->assertEquals('T2', $cached->payload['items'][0]['task']['id']);
    }
}
