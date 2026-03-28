<?php

namespace App\Console\Commands;

use App\Models\TaskViewCache;
use App\Models\User;
use App\Services\Google\GoogleOAuthTokenService;
use App\Services\Google\GoogleTasksClient;
use App\Services\Google\GoogleTasksRateLimitedException;
use App\Services\Google\TaskPriorityCodec;
use App\Services\Google\TaskViewAggregator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshTaskViewCache extends Command
{
    protected $signature = 'views:refresh-cache
        {user? : Specific user ID to refresh (omit for all connected users)}
        {--views=today,inbox,all : Comma-separated views to compute}';

    protected $description = 'Pre-compute and cache Today, Inbox, and All Lists views for users with active Google Tasks connections.';

    public function handle(): int
    {
        $views = array_map('trim', explode(',', $this->option('views')));
        $priorityCodec = app(TaskPriorityCodec::class);

        $userId = $this->argument('user');
        $query = User::query()->whereNotNull('google_refresh_token');

        if ($userId !== null) {
            $query->where('id', (int) $userId);
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            $this->info('No users with Google Tasks connection found.');

            return self::SUCCESS;
        }

        $refreshed = 0;
        $skipped = 0;

        foreach ($users as $user) {
            try {
                $client = new GoogleTasksClient($user, app(GoogleOAuthTokenService::class));
                $aggregator = new TaskViewAggregator;
                $viewCount = 0;

                foreach ($views as $view) {
                    $this->refreshView($user, $client, $aggregator, $priorityCodec, $view);
                    $viewCount++;
                }

                $refreshed++;
                $this->line("  ✓ User {$user->id}: refreshed {$viewCount} views");
            } catch (GoogleTasksRateLimitedException $e) {
                $skipped++;
                $this->warn("  ⚠ User {$user->id}: rate-limited (retry after {$e->retryAfterSeconds}s)");
                Log::warning('view_cache_refresh_rate_limited', [
                    'user_id' => $user->id,
                    'retry_after' => $e->retryAfterSeconds,
                ]);
            } catch (\Throwable $e) {
                $skipped++;
                $this->warn("  ⚠ User {$user->id}: {$e->getMessage()}");
                Log::warning('view_cache_refresh_failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Done: {$refreshed} refreshed, {$skipped} skipped.");

        return self::SUCCESS;
    }

    private function refreshView(
        User $user,
        GoogleTasksClient $client,
        TaskViewAggregator $aggregator,
        TaskPriorityCodec $priorityCodec,
        string $view,
    ): void {
        $payload = match ($view) {
            'today' => [
                'items' => $this->decodeTodayRows($priorityCodec, $aggregator->today($client)),
            ],
            'inbox' => $this->computeInbox($client, $aggregator, $priorityCodec),
            'all' => [
                'items' => $this->decodeTodayRows($priorityCodec, $aggregator->allListsTasks($client, false)),
            ],
            default => throw new \InvalidArgumentException("Unknown view: {$view}"),
        };

        TaskViewCache::putCache($user->id, $view, $this->paramsForView($view), $payload);
    }

    private function computeInbox(
        GoogleTasksClient $client,
        TaskViewAggregator $aggregator,
        TaskPriorityCodec $priorityCodec,
    ): array {
        $lists = $client->listTaskLists();
        $listItems = $lists['items'] ?? [];
        $default = $aggregator->resolveDefaultList($listItems);

        if ($default === null) {
            return ['taskList' => null, 'items' => []];
        }

        $taskListId = $default['id'] ?? '';
        $tasks = $client->listTasks($taskListId, [
            'showCompleted' => true,
            'showDeleted' => false,
            'showHidden' => false,
        ]);

        return [
            'taskList' => $default,
            'items' => $this->decodeTasks($priorityCodec, $tasks['items'] ?? []),
        ];
    }

    private function paramsForView(string $view): array
    {
        return match ($view) {
            'inbox' => ['showCompleted' => true],
            'all' => ['showCompleted' => false],
            default => [],
        };
    }

    private function decodeTasks(TaskPriorityCodec $codec, array $tasks): array
    {
        return array_map(fn (array $task) => $codec->decodeTask($task), $tasks);
    }

    private function decodeTodayRows(TaskPriorityCodec $codec, array $rows): array
    {
        return array_map(function (array $row) use ($codec) {
            $row['task'] = $codec->decodeTask($row['task'] ?? []);

            return $row;
        }, $rows);
    }
}
