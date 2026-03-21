<?php

namespace App\Services\Google;

class TaskSearcher
{
    public function __construct(
        private readonly TaskPriorityCodec $priorityCodec,
        private readonly int $maxResultsPerRequest = 100,
        private readonly int $maxMatches = 75,
    ) {}

    /**
     * Search task titles and notes across all lists (Google Tasks API — no native search).
     *
     * @return array{items: array<int, array<string, mixed>>, truncated: bool}
     */
    public function search(GoogleTasksClient $client, string $query): array
    {
        $query = trim($query);
        if ($query === '' || mb_strlen($query) < 2) {
            return ['items' => [], 'truncated' => false];
        }

        $listsResponse = $client->listTaskLists();
        $lists = $listsResponse['items'] ?? [];
        $items = [];
        $truncated = false;
        $done = false;

        foreach ($lists as $list) {
            if ($done) {
                break;
            }
            $listId = $list['id'] ?? '';
            $listTitle = $list['title'] ?? 'List';
            if ($listId === '') {
                continue;
            }

            $pageToken = null;
            do {
                if ($done) {
                    break;
                }
                $queryParams = [
                    'showCompleted' => true,
                    'showDeleted' => false,
                    'showHidden' => false,
                    'maxResults' => $this->maxResultsPerRequest,
                ];
                if ($pageToken !== null) {
                    $queryParams['pageToken'] = $pageToken;
                }

                $tasksResponse = $client->listTasks($listId, $queryParams);
                $tasks = $tasksResponse['items'] ?? [];
                $pageToken = $tasksResponse['nextPageToken'] ?? null;

                foreach ($tasks as $task) {
                    $task = $this->priorityCodec->decodeTask($task);
                    $title = $task['title'] ?? '';
                    $notes = $task['notes'] ?? null;
                    if (! $this->matches($title, $notes, $query)) {
                        continue;
                    }
                    $items[] = [
                        'task' => $task,
                        'taskListId' => $listId,
                        'taskListTitle' => $listTitle,
                        'snippet' => $this->snippet($title, $notes, $query),
                    ];
                    if (count($items) >= $this->maxMatches) {
                        $truncated = true;
                        $done = true;
                        break;
                    }
                }
            } while ($pageToken !== null && ! $done);
        }

        return ['items' => $items, 'truncated' => $truncated];
    }

    private function matches(string $title, ?string $notes, string $query): bool
    {
        $words = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY);
        if ($words === []) {
            return false;
        }
        $haystack = $title."\n".($notes ?? '');
        foreach ($words as $word) {
            if (mb_stripos($haystack, $word) === false) {
                return false;
            }
        }

        return true;
    }

    private function snippet(string $title, ?string $notes, string $query): string
    {
        $words = preg_split('/\s+/u', trim($query), -1, PREG_SPLIT_NO_EMPTY);
        $needle = $words[0] ?? $query;
        $body = (($notes !== null && $notes !== '') ? $notes : $title);
        $pos = mb_stripos($body, $needle);
        if ($pos === false) {
            return $this->ellipsis($body, 160);
        }
        $start = max(0, $pos - 50);
        $slice = mb_substr($body, $start, 160);
        if ($start > 0) {
            $slice = '…'.$slice;
        }
        if (mb_strlen($body) > $start + 160) {
            $slice .= '…';
        }

        return $slice;
    }

    private function ellipsis(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, max(0, $max - 1)).'…';
    }
}
