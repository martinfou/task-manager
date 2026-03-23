<?php

namespace App\Services\Semantic;

use App\Models\TaskEmbedding;
use App\Models\User;

class TaskDuplicateDetector
{
    /**
     * Find candidate duplicate pairs among incomplete tasks.
     *
     * Returns pairs sorted by similarity (highest first). Each pair appears
     * once (A-B, not also B-A). Uses embeddings from task_embeddings table.
     *
     * @return array{pairs: array<int, array<string, mixed>>, index_empty: bool}
     */
    public function detect(User $user, float $threshold = 0.0): array
    {
        $threshold = $threshold > 0.0
            ? $threshold
            : (float) config('google-tasks.semantic_search.duplicate_threshold', 0.82);

        $rows = TaskEmbedding::query()
            ->where('user_id', $user->id)
            ->where('status', 'needsAction')
            ->get();

        if ($rows->isEmpty()) {
            return ['pairs' => [], 'index_empty' => true];
        }

        // Build vectors array for pairwise comparison
        $items = [];
        foreach ($rows as $row) {
            /** @var array<int, float> $emb */
            $emb = $row->embedding;
            if (! is_array($emb) || $emb === []) {
                continue;
            }
            $items[] = [
                'row' => $row,
                'embedding' => $emb,
            ];
        }

        $count = count($items);
        $pairs = [];

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $a = $items[$i]['embedding'];
                $b = $items[$j]['embedding'];
                if (count($a) !== count($b)) {
                    continue;
                }
                $score = EmbeddingMath::cosineSimilarity($a, $b);
                if ($score >= $threshold) {
                    $pairs[] = [
                        'score' => round($score, 4),
                        'taskA' => $this->formatRow($items[$i]['row']),
                        'taskB' => $this->formatRow($items[$j]['row']),
                        'keeperHint' => $this->keeperHint(
                            $items[$i]['row']->notes_raw,
                            $items[$j]['row']->notes_raw,
                        ),
                    ];
                }
            }
        }

        // Sort by similarity descending
        usort($pairs, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        // Cap at reasonable number
        $maxPairs = (int) config('google-tasks.semantic_search.max_duplicate_pairs', 50);
        $pairs = array_slice($pairs, 0, $maxPairs);

        return ['pairs' => $pairs, 'index_empty' => false];
    }

    private function formatRow(TaskEmbedding $row): array
    {
        return [
            'taskId' => $row->task_id,
            'taskListId' => $row->task_list_id,
            'taskListTitle' => $row->task_list_title,
            'title' => $row->title_raw,
            'notes' => $row->notes_raw,
            'status' => $row->status,
        ];
    }

    /**
     * Returns 'A', 'B', or null. Suggests which task to keep based on notes.
     */
    private function keeperHint(?string $notesA, ?string $notesB): ?string
    {
        $lenA = mb_strlen(trim($notesA ?? ''));
        $lenB = mb_strlen(trim($notesB ?? ''));

        // Only suggest when there's a clear difference
        if ($lenA === 0 && $lenB === 0) {
            return null;
        }
        if ($lenA > 0 && $lenB === 0) {
            return 'A';
        }
        if ($lenB > 0 && $lenA === 0) {
            return 'B';
        }
        // Both have notes: suggest the one with substantially more
        if ($lenA >= $lenB * 2 && $lenA > 20) {
            return 'A';
        }
        if ($lenB >= $lenA * 2 && $lenB > 20) {
            return 'B';
        }

        return null;
    }
}
