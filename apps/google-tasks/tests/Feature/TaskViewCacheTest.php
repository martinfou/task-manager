<?php

namespace Tests\Feature;

use App\Models\TaskViewCache;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskViewCacheTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'timezone' => 'America/Toronto',
            'google_refresh_token' => 'test-refresh-token',
        ], $attrs));
    }

    public function test_put_cache_stores_payload(): void
    {
        $user = $this->makeUser();
        $payload = ['items' => [['task' => ['id' => 't1', 'title' => 'Buy milk']]]];

        $cache = TaskViewCache::putCache($user->id, 'today', [], $payload);

        $this->assertDatabaseHas('task_view_cache', [
            'user_id' => $user->id,
            'view_name' => 'today',
        ]);
        $this->assertEquals($payload, $cache->payload);
        $this->assertNull($cache->stale_at);
    }

    public function test_get_cached_returns_entry(): void
    {
        $user = $this->makeUser();
        $payload = ['items' => [['task' => ['id' => 't1']]]];

        TaskViewCache::putCache($user->id, 'today', [], $payload);

        $cached = TaskViewCache::getCached($user->id, 'today');
        $this->assertNotNull($cached);
        $this->assertEquals($payload, $cached->payload);
    }

    public function test_get_cached_returns_null_when_no_entry(): void
    {
        $user = $this->makeUser();

        $cached = TaskViewCache::getCached($user->id, 'today');
        $this->assertNull($cached);
    }

    public function test_fresh_cache_is_not_stale(): void
    {
        $user = $this->makeUser();
        TaskViewCache::putCache($user->id, 'today', [], ['items' => []]);

        $cached = TaskViewCache::getCached($user->id, 'today');
        $this->assertTrue($cached->isFresh());
    }

    public function test_mark_stale_for_user_sets_stale_flag(): void
    {
        $user = $this->makeUser();
        TaskViewCache::putCache($user->id, 'today', [], ['items' => []]);
        TaskViewCache::putCache($user->id, 'inbox', ['showCompleted' => true], ['items' => []]);
        TaskViewCache::putCache($user->id, 'all', ['showCompleted' => false], ['items' => []]);

        TaskViewCache::markStaleForUser($user->id);

        $today = TaskViewCache::getCached($user->id, 'today');
        $inbox = TaskViewCache::getCached($user->id, 'inbox', ['showCompleted' => true]);
        $all = TaskViewCache::getCached($user->id, 'all', ['showCompleted' => false]);

        $this->assertFalse($today->isFresh());
        $this->assertFalse($inbox->isFresh());
        $this->assertFalse($all->isFresh());
    }

    public function test_mark_stale_does_not_affect_other_users(): void
    {
        $user1 = $this->makeUser();
        $user2 = $this->makeUser();
        TaskViewCache::putCache($user1->id, 'today', [], ['items' => []]);
        TaskViewCache::putCache($user2->id, 'today', [], ['items' => []]);

        TaskViewCache::markStaleForUser($user1->id);

        $this->assertFalse(TaskViewCache::getCached($user1->id, 'today')->isFresh());
        $this->assertTrue(TaskViewCache::getCached($user2->id, 'today')->isFresh());
    }

    public function test_put_cache_clears_stale_flag(): void
    {
        $user = $this->makeUser();
        TaskViewCache::putCache($user->id, 'today', [], ['items' => []]);
        TaskViewCache::markStaleForUser($user->id);

        $this->assertFalse(TaskViewCache::getCached($user->id, 'today')->isFresh());

        // Re-cache should clear stale
        TaskViewCache::putCache($user->id, 'today', [], ['items' => [['task' => ['id' => 'new']]]]);
        $this->assertTrue(TaskViewCache::getCached($user->id, 'today')->isFresh());
    }

    public function test_params_hash_differentiates_cache_entries(): void
    {
        $user = $this->makeUser();
        TaskViewCache::putCache($user->id, 'inbox', ['showCompleted' => true], ['items' => ['completed']]);
        TaskViewCache::putCache($user->id, 'inbox', ['showCompleted' => false], ['items' => ['active']]);

        $this->assertDatabaseCount('task_view_cache', 2);

        $withCompleted = TaskViewCache::getCached($user->id, 'inbox', ['showCompleted' => true]);
        $withoutCompleted = TaskViewCache::getCached($user->id, 'inbox', ['showCompleted' => false]);

        $this->assertEquals(['items' => ['completed']], $withCompleted->payload);
        $this->assertEquals(['items' => ['active']], $withoutCompleted->payload);
    }

    public function test_purge_for_user_deletes_all_entries(): void
    {
        $user = $this->makeUser();
        TaskViewCache::putCache($user->id, 'today', [], ['items' => []]);
        TaskViewCache::putCache($user->id, 'inbox', [], ['items' => []]);
        TaskViewCache::putCache($user->id, 'all', [], ['items' => []]);

        TaskViewCache::purgeForUser($user->id);

        $this->assertDatabaseCount('task_view_cache', 0);
    }

    public function test_old_cache_is_stale(): void
    {
        $user = $this->makeUser();

        TaskViewCache::create([
            'user_id' => $user->id,
            'view_name' => 'today',
            'params_hash' => '',
            'payload' => ['items' => []],
            'computed_at' => now()->subMinutes(10),
        ]);

        $cached = TaskViewCache::getCached($user->id, 'today');
        $this->assertFalse($cached->isFresh(5));
    }
}
