<?php

namespace App\Services\Google;

class TaskPriorityCodec
{
    private const DEFAULT_PRIORITY = 'p3';

    /**
     * @param  array<string, mixed>  $task
     * @return array<string, mixed>
     */
    public function decodeTask(array $task): array
    {
        $title = (string) ($task['title'] ?? '');
        $parsed = $this->parseTitle($title);

        $task['titleRaw'] = $title;
        $task['title'] = $parsed['title'];
        $task['priority'] = $parsed['priority'];

        return $task;
    }

    public function encodeTitle(string $title, ?string $priority): string
    {
        $normalizedPriority = $this->normalizePriority($priority);
        $strippedTitle = $this->parseTitle($title)['title'];

        return sprintf('[%s] %s', strtoupper($normalizedPriority), $strippedTitle);
    }

    /**
     * @return array{title: string, priority: string}
     */
    private function parseTitle(string $title): array
    {
        $trimmed = trim($title);
        if ($trimmed === '') {
            return [
                'title' => '',
                'priority' => self::DEFAULT_PRIORITY,
            ];
        }

        if (preg_match('/^\[(P[1-4])\]\s*(.*)$/i', $trimmed, $matches) === 1) {
            return [
                'title' => trim((string) ($matches[2] ?? '')),
                'priority' => strtolower((string) ($matches[1] ?? self::DEFAULT_PRIORITY)),
            ];
        }

        return [
            'title' => $trimmed,
            'priority' => self::DEFAULT_PRIORITY,
        ];
    }

    private function normalizePriority(?string $priority): string
    {
        $normalized = strtolower(trim((string) $priority));
        if (in_array($normalized, ['p1', 'p2', 'p3', 'p4'], true)) {
            return $normalized;
        }

        return self::DEFAULT_PRIORITY;
    }
}
