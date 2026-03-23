<?php

namespace App\Services\Google;

use App\Models\DashboardStatsCache;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

class DashboardStatsService
{
    /**
     * Compute dashboard stats from Google Tasks API data.
     *
     * Definitions:
     * - "Created": prefers Task.created from Google when present; falls back to Task.updated.
     * - "Completed": Task.completed timestamp (RFC 3339), present on completed tasks.
     *
     * @return array{daily: list<array>, insights: array, weekday: array, leadTime: array, dueDiscipline: array, timezone: string, rangeLabel: string, computedAt: string}
     */
    public function compute(User $user, GoogleTasksClient $client, int $rangeDays = 30): array
    {
        $tz = $user->timezone ?? 'America/Toronto';
        $now = CarbonImmutable::now($tz);
        $rangeStart = $now->startOfDay()->subDays($rangeDays - 1);
        $priorStart = $rangeStart->subDays($rangeDays);

        $allTasks = $this->fetchAllTasks($client);

        $daily = $this->bucketDaily($allTasks, $rangeStart, $now, $tz);
        $priorDaily = $this->bucketDaily($allTasks, $priorStart, $rangeStart->subDay(), $tz);

        $insights = $this->computeInsights($daily, $priorDaily, $rangeDays);
        $weekday = $this->computeWeekday($daily);
        $leadTime = $this->computeLeadTime($allTasks, $rangeStart, $now, $tz);
        $dueDiscipline = $this->computeDueDiscipline($allTasks, $rangeStart, $now, $tz);

        $stats = [
            'daily' => $daily,
            'insights' => $insights,
            'weekday' => $weekday,
            'leadTime' => $leadTime,
            'dueDiscipline' => $dueDiscipline,
            'timezone' => $tz,
            'rangeDays' => $rangeDays,
            'computedAt' => $now->toIso8601String(),
        ];

        // Cache for offline access
        DashboardStatsCache::updateOrCreate(
            ['user_id' => $user->id],
            ['stats' => $stats, 'computed_at' => now()],
        );

        return $stats;
    }

    /**
     * Get cached stats for disconnected state.
     */
    public function getCached(User $user): ?array
    {
        $cache = DashboardStatsCache::where('user_id', $user->id)->first();
        if (! $cache) {
            return null;
        }

        return [
            ...$cache->stats,
            'cached' => true,
            'cachedAt' => $cache->computed_at->toIso8601String(),
        ];
    }

    /**
     * Fetch all tasks from all lists (including completed).
     *
     * @return list<array{task: array, listTitle: string}>
     */
    private function fetchAllTasks(GoogleTasksClient $client): array
    {
        $lists = $client->listTaskLists();
        $out = [];

        foreach ($lists['items'] ?? [] as $list) {
            $id = $list['id'] ?? '';
            if ($id === '') {
                continue;
            }
            $listTitle = (string) ($list['title'] ?? '');
            $tasks = $client->listTasks($id, [
                'showCompleted' => true,
                'showDeleted' => false,
                'showHidden' => true,
            ]);
            foreach ($tasks['items'] ?? [] as $task) {
                $out[] = ['task' => $task, 'listTitle' => $listTitle];
            }
        }

        return $out;
    }

    /**
     * Bucket tasks into daily created/completed counts.
     *
     * @return list<array{date: string, created: int, completed: int}>
     */
    private function bucketDaily(array $allTasks, CarbonImmutable $start, CarbonImmutable $end, string $tz): array
    {
        $days = [];
        $cursor = $start->startOfDay();
        while ($cursor->lte($end->startOfDay())) {
            $days[$cursor->format('Y-m-d')] = ['created' => 0, 'completed' => 0];
            $cursor = $cursor->addDay();
        }

        foreach ($allTasks as $row) {
            $task = $row['task'];

            // Bucket creation
            $createdAt = $this->parseCreatedDate($task, $tz);
            if ($createdAt) {
                $key = $createdAt->format('Y-m-d');
                if (isset($days[$key])) {
                    $days[$key]['created']++;
                }
            }

            // Bucket completion
            $completedAt = $this->parseCompletedDate($task, $tz);
            if ($completedAt) {
                $key = $completedAt->format('Y-m-d');
                if (isset($days[$key])) {
                    $days[$key]['completed']++;
                }
            }
        }

        $result = [];
        foreach ($days as $date => $counts) {
            $result[] = ['date' => $date, ...$counts];
        }

        return $result;
    }

    /**
     * Compute insight cards from daily data.
     */
    private function computeInsights(array $daily, array $priorDaily, int $rangeDays): array
    {
        $totalCreated = array_sum(array_column($daily, 'created'));
        $totalCompleted = array_sum(array_column($daily, 'completed'));
        $netFlow = $totalCompleted - $totalCreated;
        $activeDays = count(array_filter($daily, fn ($d) => $d['completed'] > 0));

        $priorCompleted = array_sum(array_column($priorDaily, 'completed'));
        $priorCreated = array_sum(array_column($priorDaily, 'created'));
        $hasPrior = count($priorDaily) > 0 && ($priorCompleted > 0 || $priorCreated > 0);

        $completionDelta = null;
        $creationDelta = null;
        if ($hasPrior && $priorCompleted > 0) {
            $completionDelta = round(($totalCompleted - $priorCompleted) / $priorCompleted * 100);
        }
        if ($hasPrior && $priorCreated > 0) {
            $creationDelta = round(($totalCreated - $priorCreated) / $priorCreated * 100);
        }

        return [
            'totalCreated' => $totalCreated,
            'totalCompleted' => $totalCompleted,
            'netFlow' => $netFlow,
            'activeDays' => $activeDays,
            'totalDays' => count($daily),
            'hasPrior' => $hasPrior,
            'priorCompleted' => $priorCompleted,
            'priorCreated' => $priorCreated,
            'completionDelta' => $completionDelta,
            'creationDelta' => $creationDelta,
        ];
    }

    /**
     * Compute weekday completion pattern.
     *
     * @return array{days: list<array{day: int, name: string, count: int}>, peakDay: string|null}
     */
    private function computeWeekday(array $daily): array
    {
        $byDow = array_fill(0, 7, 0); // 0=Sun .. 6=Sat
        $dowNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        foreach ($daily as $d) {
            $dow = Carbon::parse($d['date'])->dayOfWeek;
            $byDow[$dow] += $d['completed'];
        }

        $days = [];
        foreach ($byDow as $i => $count) {
            $days[] = ['day' => $i, 'name' => $dowNames[$i], 'count' => $count];
        }

        $max = max($byDow);
        $peakDay = $max > 0 ? $dowNames[array_search($max, $byDow)] : null;

        return ['days' => $days, 'peakDay' => $peakDay];
    }

    /**
     * Compute lead time: days from creation to completion for tasks completed in range.
     */
    private function computeLeadTime(array $allTasks, CarbonImmutable $start, CarbonImmutable $end, string $tz): array
    {
        $leadTimes = [];

        foreach ($allTasks as $row) {
            $task = $row['task'];
            $completedAt = $this->parseCompletedDate($task, $tz);
            if (! $completedAt) {
                continue;
            }
            if ($completedAt->lt($start) || $completedAt->gt($end)) {
                continue;
            }

            $createdAt = $this->parseCreatedDate($task, $tz);
            if (! $createdAt) {
                continue;
            }

            $days = $createdAt->startOfDay()->diffInDays($completedAt->startOfDay());
            $leadTimes[] = (int) $days;
        }

        if (empty($leadTimes)) {
            return ['median' => null, 'p90' => null, 'count' => 0, 'longTail' => 0];
        }

        sort($leadTimes);
        $count = count($leadTimes);
        $median = $this->percentile($leadTimes, 50);
        $p90 = $this->percentile($leadTimes, 90);
        $longTail = count(array_filter($leadTimes, fn ($d) => $d > 30));

        return [
            'median' => $median,
            'p90' => $p90,
            'count' => $count,
            'longTail' => $longTail,
        ];
    }

    /**
     * Compute due discipline: share of completions that were on time / overdue / no due date.
     */
    private function computeDueDiscipline(array $allTasks, CarbonImmutable $start, CarbonImmutable $end, string $tz): array
    {
        $onTime = 0;
        $overdue = 0;
        $noDue = 0;

        foreach ($allTasks as $row) {
            $task = $row['task'];
            $completedAt = $this->parseCompletedDate($task, $tz);
            if (! $completedAt) {
                continue;
            }
            if ($completedAt->lt($start) || $completedAt->gt($end)) {
                continue;
            }

            $due = $task['due'] ?? null;
            if (! $due || $due === '') {
                $noDue++;

                continue;
            }

            // Google Tasks due dates are calendar dates (T00:00:00.000Z).
            // Compare as date strings to avoid timezone shift issues.
            $dueDay = Carbon::parse($due)->format('Y-m-d');
            $completedDay = $completedAt->format('Y-m-d');

            if ($completedDay <= $dueDay) {
                $onTime++;
            } else {
                $overdue++;
            }
        }

        $total = $onTime + $overdue + $noDue;

        return [
            'onTime' => $onTime,
            'overdue' => $overdue,
            'noDue' => $noDue,
            'total' => $total,
        ];
    }

    /**
     * Parse the creation date from a task. Prefers `created`, falls back to `updated`.
     */
    private function parseCreatedDate(array $task, string $tz): ?CarbonImmutable
    {
        $raw = $task['created'] ?? $task['updated'] ?? null;
        if (! $raw || $raw === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($raw)->timezone($tz);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Parse the completion date from a task.
     */
    private function parseCompletedDate(array $task, string $tz): ?CarbonImmutable
    {
        if (($task['status'] ?? '') !== 'completed') {
            return null;
        }

        $raw = $task['completed'] ?? null;
        if (! $raw || $raw === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($raw)->timezone($tz);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * @param  list<int>  $sorted  Sorted array of values
     */
    private function percentile(array $sorted, int $p): int
    {
        $n = count($sorted);
        if ($n === 0) {
            return 0;
        }
        $idx = (int) ceil(($p / 100) * $n) - 1;

        return $sorted[max(0, min($idx, $n - 1))];
    }
}
