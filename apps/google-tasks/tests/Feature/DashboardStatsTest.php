<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Google\DashboardStatsService;
use App\Services\Google\GoogleTasksClient;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'timezone' => 'America/Toronto',
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

    public function test_returns_empty_structure_for_no_tasks(): void
    {
        $user = $this->makeUser();
        $client = $this->mockClient();
        $service = new DashboardStatsService;

        $stats = $service->compute($user, $client, 7);

        $this->assertArrayHasKey('daily', $stats);
        $this->assertArrayHasKey('insights', $stats);
        $this->assertCount(7, $stats['daily']);
        $this->assertEquals(0, $stats['insights']['totalCreated']);
        $this->assertEquals(0, $stats['insights']['totalCompleted']);
    }

    public function test_buckets_created_task_by_date(): void
    {
        $user = $this->makeUser();
        $today = CarbonImmutable::now('America/Toronto')->format('Y-m-d');
        $client = $this->mockClient(
            [['id' => 'list1', 'title' => 'My Tasks']],
            ['list1' => [
                [
                    'id' => 't1',
                    'title' => 'Test task',
                    'status' => 'needsAction',
                    'created' => $today.'T10:00:00.000Z',
                    'updated' => $today.'T10:00:00.000Z',
                ],
            ]],
        );

        $service = new DashboardStatsService;
        $stats = $service->compute($user, $client, 7);

        $totalCreated = array_sum(array_column($stats['daily'], 'created'));
        $this->assertGreaterThanOrEqual(1, $totalCreated);
    }

    public function test_buckets_completed_task(): void
    {
        $user = $this->makeUser();
        $today = CarbonImmutable::now('America/Toronto')->format('Y-m-d');
        $client = $this->mockClient(
            [['id' => 'list1', 'title' => 'My Tasks']],
            ['list1' => [
                [
                    'id' => 't1',
                    'title' => 'Done task',
                    'status' => 'completed',
                    'created' => $today.'T08:00:00.000Z',
                    'completed' => $today.'T12:00:00.000Z',
                    'updated' => $today.'T12:00:00.000Z',
                ],
            ]],
        );

        $service = new DashboardStatsService;
        $stats = $service->compute($user, $client, 7);

        $totalCompleted = array_sum(array_column($stats['daily'], 'completed'));
        $this->assertGreaterThanOrEqual(1, $totalCompleted);
    }

    public function test_net_flow_computed(): void
    {
        $user = $this->makeUser();
        $today = CarbonImmutable::now('America/Toronto')->format('Y-m-d');
        $client = $this->mockClient(
            [['id' => 'list1', 'title' => 'My Tasks']],
            ['list1' => [
                [
                    'id' => 't1',
                    'title' => 'Task A',
                    'status' => 'needsAction',
                    'created' => $today.'T08:00:00.000Z',
                    'updated' => $today.'T08:00:00.000Z',
                ],
                [
                    'id' => 't2',
                    'title' => 'Task B',
                    'status' => 'needsAction',
                    'created' => $today.'T09:00:00.000Z',
                    'updated' => $today.'T09:00:00.000Z',
                ],
                [
                    'id' => 't3',
                    'title' => 'Task C',
                    'status' => 'completed',
                    'created' => $today.'T07:00:00.000Z',
                    'completed' => $today.'T10:00:00.000Z',
                    'updated' => $today.'T10:00:00.000Z',
                ],
            ]],
        );

        $service = new DashboardStatsService;
        $stats = $service->compute($user, $client, 7);

        // 3 created, 1 completed => net flow = 1 - 3 = -2
        $this->assertEquals(-2, $stats['insights']['netFlow']);
    }

    public function test_weekday_pattern(): void
    {
        $user = $this->makeUser();
        $today = CarbonImmutable::now('America/Toronto');
        $client = $this->mockClient(
            [['id' => 'list1', 'title' => 'My Tasks']],
            ['list1' => [
                [
                    'id' => 't1',
                    'title' => 'Done',
                    'status' => 'completed',
                    'created' => $today->subDays(1)->toIso8601String(),
                    'completed' => $today->toIso8601String(),
                    'updated' => $today->toIso8601String(),
                ],
            ]],
        );

        $service = new DashboardStatsService;
        $stats = $service->compute($user, $client, 7);

        $this->assertArrayHasKey('weekday', $stats);
        $this->assertCount(7, $stats['weekday']['days']);
        $this->assertArrayHasKey('created', $stats['weekday']['days'][0]);
        $this->assertArrayHasKey('completed', $stats['weekday']['days'][0]);
    }

    public function test_lead_time_computed(): void
    {
        $user = $this->makeUser();
        $today = CarbonImmutable::now('America/Toronto');
        $client = $this->mockClient(
            [['id' => 'list1', 'title' => 'My Tasks']],
            ['list1' => [
                [
                    'id' => 't1',
                    'title' => 'Quick task',
                    'status' => 'completed',
                    'created' => $today->subDays(3)->toIso8601String(),
                    'completed' => $today->toIso8601String(),
                    'updated' => $today->toIso8601String(),
                ],
                [
                    'id' => 't2',
                    'title' => 'Long task',
                    'status' => 'completed',
                    'created' => $today->subDays(10)->toIso8601String(),
                    'completed' => $today->toIso8601String(),
                    'updated' => $today->toIso8601String(),
                ],
            ]],
        );

        $service = new DashboardStatsService;
        $stats = $service->compute($user, $client, 14);

        $this->assertNotNull($stats['leadTime']['median']);
        $this->assertNotNull($stats['leadTime']['p90']);
        $this->assertEquals(2, $stats['leadTime']['count']);
    }

    public function test_due_discipline_counts(): void
    {
        $user = $this->makeUser();
        $today = CarbonImmutable::now('America/Toronto');
        $todayStr = $today->format('Y-m-d');
        $overdueStr = $today->subDays(2)->format('Y-m-d');

        $client = $this->mockClient(
            [['id' => 'list1', 'title' => 'My Tasks']],
            ['list1' => [
                // On time: completed today, due today
                [
                    'id' => 't1',
                    'title' => 'On time',
                    'status' => 'completed',
                    'due' => $todayStr.'T00:00:00.000Z',
                    'created' => $today->subDays(1)->toIso8601String(),
                    'completed' => $today->toIso8601String(),
                    'updated' => $today->toIso8601String(),
                ],
                // Overdue: completed today, was due 2 days ago
                [
                    'id' => 't2',
                    'title' => 'Late',
                    'status' => 'completed',
                    'due' => $overdueStr.'T00:00:00.000Z',
                    'created' => $today->subDays(5)->toIso8601String(),
                    'completed' => $today->toIso8601String(),
                    'updated' => $today->toIso8601String(),
                ],
                // No due date
                [
                    'id' => 't3',
                    'title' => 'No due',
                    'status' => 'completed',
                    'created' => $today->subDays(1)->toIso8601String(),
                    'completed' => $today->toIso8601String(),
                    'updated' => $today->toIso8601String(),
                ],
            ]],
        );

        $service = new DashboardStatsService;
        $stats = $service->compute($user, $client, 7);

        $this->assertEquals(1, $stats['dueDiscipline']['onTime']);
        $this->assertEquals(1, $stats['dueDiscipline']['overdue']);
        $this->assertEquals(1, $stats['dueDiscipline']['noDue']);
    }

    public function test_caches_stats_for_offline(): void
    {
        $user = $this->makeUser();
        $client = $this->mockClient();
        $service = new DashboardStatsService;

        $service->compute($user, $client, 7);

        $cached = $service->getCached($user, 7);
        $this->assertNotNull($cached);
        $this->assertTrue($cached['cached']);
    }

    public function test_range_boundary_7_days(): void
    {
        $user = $this->makeUser();
        $client = $this->mockClient();
        $service = new DashboardStatsService;

        $stats = $service->compute($user, $client, 7);

        $this->assertCount(7, $stats['daily']);
        $this->assertEquals(7, $stats['rangeDays']);
    }
}
