<?php

namespace Tests\Feature;

use App\Models\DashboardStatsCache;
use App\Models\User;
use App\Services\Google\DashboardStatsService;
use App\Services\Google\GoogleTasksClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DashboardStatsCacheTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'timezone' => 'America/Toronto',
            'google_refresh_token' => 'test-refresh-token',
        ], $attrs));
    }

    private function mockClient(array $lists = [], array $tasksByList = []): GoogleTasksClient
    {
        $client = Mockery::mock(GoogleTasksClient::class);
        $client->shouldReceive('listTaskLists')
            ->andReturn(['items' => $lists]);

        foreach ($tasksByList as $listId => $tasks) {
            $client->shouldReceive('listTasks')
                ->with($listId, Mockery::any())
                ->andReturn(['items' => $tasks]);
        }

        return $client;
    }

    public function test_compute_stores_cache_per_range(): void
    {
        $user = $this->makeUser();
        $client = $this->mockClient();
        $service = new DashboardStatsService;

        $service->compute($user, $client, 7);
        $service->compute($user, $client, 30);

        $this->assertDatabaseCount('dashboard_stats_cache', 2);
        $this->assertDatabaseHas('dashboard_stats_cache', [
            'user_id' => $user->id,
            'range_days' => 7,
        ]);
        $this->assertDatabaseHas('dashboard_stats_cache', [
            'user_id' => $user->id,
            'range_days' => 30,
        ]);
    }

    public function test_get_cached_returns_correct_range(): void
    {
        $user = $this->makeUser();
        $client = $this->mockClient();
        $service = new DashboardStatsService;

        $service->compute($user, $client, 7);
        $service->compute($user, $client, 30);

        $cached7 = $service->getCached($user, 7);
        $cached30 = $service->getCached($user, 30);
        $cached14 = $service->getCached($user, 14);

        $this->assertNotNull($cached7);
        $this->assertEquals(7, $cached7['rangeDays']);
        $this->assertNotNull($cached30);
        $this->assertEquals(30, $cached30['rangeDays']);
        $this->assertNull($cached14);
    }

    public function test_fresh_cache_is_not_stale(): void
    {
        $user = $this->makeUser();
        $client = $this->mockClient();
        $service = new DashboardStatsService;

        $service->compute($user, $client, 30);

        $cached = $service->getCachedIfFresh($user, 30);
        $this->assertNotNull($cached);
        $this->assertFalse($cached['stale']);
    }

    public function test_mark_stale_for_user_sets_stale_flag(): void
    {
        $user = $this->makeUser();
        $client = $this->mockClient();
        $service = new DashboardStatsService;

        $service->compute($user, $client, 7);
        $service->compute($user, $client, 30);

        DashboardStatsCache::markStaleForUser($user->id);

        $cached7 = $service->getCachedIfFresh($user, 7);
        $cached30 = $service->getCachedIfFresh($user, 30);

        $this->assertTrue($cached7['stale']);
        $this->assertTrue($cached30['stale']);
    }

    public function test_recompute_clears_stale_flag(): void
    {
        $user = $this->makeUser();
        $client = $this->mockClient();
        $service = new DashboardStatsService;

        $service->compute($user, $client, 30);
        DashboardStatsCache::markStaleForUser($user->id);

        // Verify it's stale
        $cached = $service->getCachedIfFresh($user, 30);
        $this->assertTrue($cached['stale']);

        // Recompute
        $service->compute($user, $client, 30);

        $cached = $service->getCachedIfFresh($user, 30);
        $this->assertFalse($cached['stale']);
    }

    public function test_old_cache_is_stale(): void
    {
        $user = $this->makeUser();

        DashboardStatsCache::create([
            'user_id' => $user->id,
            'range_days' => 30,
            'stats' => ['daily' => [], 'insights' => [], 'rangeDays' => 30],
            'computed_at' => now()->subMinutes(60),
        ]);

        $service = new DashboardStatsService;
        $cached = $service->getCachedIfFresh($user, 30);

        $this->assertNotNull($cached);
        $this->assertTrue($cached['stale']);
    }

    public function test_mark_stale_does_not_affect_other_users(): void
    {
        $user1 = $this->makeUser();
        $user2 = $this->makeUser();
        $client = $this->mockClient();
        $service = new DashboardStatsService;

        $service->compute($user1, $client, 30);
        $service->compute($user2, $client, 30);

        DashboardStatsCache::markStaleForUser($user1->id);

        $cached1 = $service->getCachedIfFresh($user1, 30);
        $cached2 = $service->getCachedIfFresh($user2, 30);

        $this->assertTrue($cached1['stale']);
        $this->assertFalse($cached2['stale']);
    }

    public function test_scheduled_command_refreshes_connected_users(): void
    {
        $this->makeUser(['google_refresh_token' => 'token-1']);
        $this->makeUser(['google_refresh_token' => null]);

        // The command requires real Google API access, so we just verify it runs
        // and handles the "no tasks" case gracefully for users with tokens
        $this->artisan('dashboard:refresh-stats', ['--ranges' => '7'])
            ->assertSuccessful();
    }
}
