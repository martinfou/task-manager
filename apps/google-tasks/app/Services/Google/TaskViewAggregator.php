<?php

namespace App\Services\Google;

use Carbon\Carbon;

class TaskViewAggregator
{
    /**
     * Incomplete tasks with a due date on or before the end of “today” (app timezone).
     * Includes overdue items. Tasks without a due date are excluded.
     *
     * @return list<array{taskListId: string, taskListTitle: string, task: array<string, mixed>}>
     */
    public function today(GoogleTasksClient $client): array
    {
        $lists = $client->listTaskLists();
        $listItems = $lists['items'] ?? [];
        $out = [];
        $endOfToday = now()->endOfDay();

        foreach ($listItems as $list) {
            $id = $list['id'] ?? '';
            if ($id === '') {
                continue;
            }
            $tasks = $client->listTasks($id, [
                'showCompleted' => false,
                'showDeleted' => false,
                'showHidden' => false,
            ]);
            foreach ($tasks['items'] ?? [] as $task) {
                if ($this->taskBelongsInTodayView($task, $endOfToday)) {
                    $out[] = [
                        'taskListId' => $id,
                        'taskListTitle' => $list['title'] ?? '',
                        'task' => $task,
                    ];
                }
            }
        }

        usort($out, function (array $a, array $b): int {
            $da = $a['task']['due'] ?? '';
            $db = $b['task']['due'] ?? '';

            return strcmp((string) $da, (string) $db);
        });

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $listItems
     * @return array<string, mixed>|null
     */
    public function resolveDefaultList(array $listItems): ?array
    {
        foreach ($listItems as $list) {
            if (($list['title'] ?? '') === 'My Tasks') {
                return $list;
            }
        }

        return $listItems[0] ?? null;
    }

    /**
     * All tasks from every list (US-023). Used for the “All lists” aggregate view.
     *
     * @return list<array{taskListId: string, taskListTitle: string, task: array<string, mixed>}>
     */
    public function allListsTasks(GoogleTasksClient $client, bool $showCompleted): array
    {
        $lists = $client->listTaskLists();
        $listItems = $lists['items'] ?? [];
        $out = [];

        foreach ($listItems as $list) {
            $id = $list['id'] ?? '';
            if ($id === '') {
                continue;
            }
            $tasks = $client->listTasks($id, [
                'showCompleted' => $showCompleted,
                'showDeleted' => false,
                'showHidden' => false,
            ]);
            $title = (string) ($list['title'] ?? '');
            foreach ($tasks['items'] ?? [] as $task) {
                $out[] = [
                    'taskListId' => $id,
                    'taskListTitle' => $title,
                    'task' => $task,
                ];
            }
        }

        usort($out, function (array $a, array $b): int {
            $pa = $this->priorityRank($a['task']['title'] ?? '');
            $pb = $this->priorityRank($b['task']['title'] ?? '');
            if ($pa !== $pb) {
                return $pa - $pb;
            }

            $da = (string) ($a['task']['due'] ?? 'zzz');
            $db = (string) ($b['task']['due'] ?? 'zzz');

            return strcmp($da, $db);
        });

        return $out;
    }

    /**
     * Map a priority-encoded title to a sort rank (1 = highest).
     */
    private function priorityRank(string $title): int
    {
        if (preg_match('/^\[(P[1-4])\]/i', trim($title), $m) === 1) {
            return (int) substr(strtolower($m[1]), 1);
        }

        return 4; // no priority = lowest
    }

    private function taskBelongsInTodayView(array $task, Carbon $endOfToday): bool
    {
        if (($task['status'] ?? '') !== 'needsAction') {
            return false;
        }
        $due = $task['due'] ?? null;
        if ($due === null || $due === '') {
            return false;
        }

        $dueAt = Carbon::parse($due)->timezone(config('app.timezone'));

        return $dueAt->lte($endOfToday);
    }
}
