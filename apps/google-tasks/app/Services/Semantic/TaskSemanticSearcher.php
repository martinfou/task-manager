<?php

namespace App\Services\Semantic;

use App\Models\TaskEmbedding;
use App\Models\User;

class TaskSemanticSearcher
{
    public function __construct(
        private readonly EmbeddingClient $embeddings,
    ) {}

    /**
     * @return array{items: array<int, array<string, mixed>>, truncated: bool, index_empty?: bool}
     */
    public function search(User $user, string $query): array
    {
        $query = trim($query);
        if ($query === '' || mb_strlen($query) < 2) {
            return ['items' => [], 'truncated' => false];
        }

        if (! $this->embeddings->isConfigured()) {
            return ['items' => [], 'truncated' => false, 'index_empty' => true];
        }

        $queryVector = $this->embeddings->embedOne(mb_substr($query, 0, 8000));
        if ($queryVector === []) {
            return ['items' => [], 'truncated' => false];
        }

        $rows = TaskEmbedding::query()->where('user_id', $user->id)->get();
        if ($rows->isEmpty()) {
            return ['items' => [], 'truncated' => false, 'index_empty' => true];
        }

        $maxMatches = (int) config('google-tasks.semantic_search.max_results', 75);

        $scored = [];
        foreach ($rows as $row) {
            /** @var array<int, float> $emb */
            $emb = $row->embedding;
            if (! is_array($emb) || $emb === []) {
                continue;
            }
            if (count($emb) !== count($queryVector)) {
                continue;
            }
            $scored[] = [
                'row' => $row,
                'score' => EmbeddingMath::cosineSimilarity($queryVector, $emb),
            ];
        }

        usort($scored, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        $total = count($scored);
        $top = array_slice($scored, 0, $maxMatches);
        $truncated = $total > $maxMatches;

        $items = [];
        foreach ($top as $entry) {
            /** @var TaskEmbedding $row */
            $row = $entry['row'];
            $task = [
                'id' => $row->task_id,
                'title' => $row->title_raw,
                'notes' => $row->notes_raw,
                'status' => $row->status,
            ];
            $items[] = [
                'task' => $task,
                'taskListId' => $row->task_list_id,
                'taskListTitle' => $row->task_list_title,
                'snippet' => $this->snippet($row->title_raw, $row->notes_raw),
            ];
        }

        return ['items' => $items, 'truncated' => $truncated];
    }

    private function snippet(string $title, ?string $notes): string
    {
        $body = (($notes !== null && $notes !== '') ? $notes : $title);

        return $this->ellipsis($body, 160);
    }

    private function ellipsis(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, max(0, $max - 1)).'…';
    }
}
