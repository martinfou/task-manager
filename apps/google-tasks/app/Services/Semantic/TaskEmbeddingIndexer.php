<?php

namespace App\Services\Semantic;

use App\Models\TaskEmbedding;
use App\Models\User;
use App\Services\Google\GoogleTasksClient;
use App\Services\Google\TaskPriorityCodec;
use Illuminate\Support\Facades\DB;

class TaskEmbeddingIndexer
{
    public function __construct(
        private readonly EmbeddingClient $embeddings,
        private readonly TaskPriorityCodec $priorityCodec,
    ) {}

    /**
     * Full rebuild: deletes existing rows for the user, scans Google Tasks, stores embeddings.
     */
    public function reindexUser(User $user, GoogleTasksClient $client): int
    {
        if (! $this->embeddings->isConfigured()) {
            return 0;
        }

        $batchSize = max(1, (int) config('google-tasks.semantic_search.index_batch_size', 32));

        $listsResponse = $client->listTaskLists();
        $lists = $listsResponse['items'] ?? [];

        $records = [];
        foreach ($lists as $list) {
            $listId = $list['id'] ?? '';
            $listTitle = (string) ($list['title'] ?? 'List');
            if ($listId === '') {
                continue;
            }

            $pageToken = null;
            do {
                $queryParams = [
                    'showCompleted' => true,
                    'showDeleted' => false,
                    'showHidden' => false,
                    'maxResults' => 100,
                ];
                if ($pageToken !== null) {
                    $queryParams['pageToken'] = $pageToken;
                }

                $tasksResponse = $client->listTasks($listId, $queryParams);
                $tasks = $tasksResponse['items'] ?? [];
                $pageToken = $tasksResponse['nextPageToken'] ?? null;

                foreach ($tasks as $task) {
                    $task = $this->priorityCodec->decodeTask($task);
                    $tid = (string) ($task['id'] ?? '');
                    if ($tid === '') {
                        continue;
                    }
                    $titleRaw = (string) ($task['titleRaw'] ?? '');
                    $notesRaw = isset($task['notes']) ? (string) $task['notes'] : null;
                    $status = (string) ($task['status'] ?? 'needsAction');
                    $text = $this->embeddingText($titleRaw, $notesRaw);
                    $hash = $this->contentHash($listId, $tid, $titleRaw, $notesRaw);

                    $records[] = [
                        'list_id' => $listId,
                        'list_title' => $listTitle,
                        'task_id' => $tid,
                        'title_raw' => $titleRaw,
                        'notes_raw' => $notesRaw,
                        'status' => $status,
                        'hash' => $hash,
                        'text' => $text,
                    ];
                }
            } while ($pageToken !== null);
        }

        return DB::transaction(function () use ($user, $records, $batchSize): int {
            TaskEmbedding::where('user_id', $user->id)->delete();

            $inserted = 0;
            foreach (array_chunk($records, $batchSize) as $chunk) {
                $texts = array_map(fn (array $r): string => $r['text'], $chunk);
                $vectors = $this->embeddings->embedBatch($texts);
                $dim = count($vectors[0] ?? []);

                foreach ($chunk as $i => $r) {
                    $vector = $vectors[$i] ?? [];
                    if ($vector === [] || $dim === 0) {
                        continue;
                    }
                    TaskEmbedding::create([
                        'user_id' => $user->id,
                        'task_list_id' => $r['list_id'],
                        'task_id' => $r['task_id'],
                        'content_hash' => $r['hash'],
                        'dimensions' => $dim,
                        'embedding' => $vector,
                        'task_list_title' => $r['list_title'],
                        'title_raw' => $r['title_raw'],
                        'notes_raw' => $r['notes_raw'],
                        'status' => $r['status'],
                    ]);
                    $inserted++;
                }
            }

            return $inserted;
        });
    }

    private function embeddingText(string $titleRaw, ?string $notesRaw): string
    {
        $notes = $notesRaw ?? '';
        $combined = $titleRaw."\n".$notes;

        return mb_substr($combined, 0, 8000);
    }

    private function contentHash(string $listId, string $taskId, string $title, ?string $notes): string
    {
        return hash('sha256', $listId.'|'.$taskId.'|'.$title.'|'.($notes ?? ''));
    }
}
