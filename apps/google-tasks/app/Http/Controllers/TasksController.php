<?php

namespace App\Http\Controllers;

use App\Services\Google\GoogleOAuthTokenService;
use App\Services\Google\GoogleTasksApiException;
use App\Services\Google\GoogleTasksClient;
use App\Services\Google\GoogleTasksRateLimitedException;
use App\Services\Google\TaskPriorityCodec;
use App\Services\Google\TaskSearcher;
use App\Services\Google\TaskViewAggregator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TasksController extends Controller
{
    public function __construct(
        private readonly TaskPriorityCodec $priorityCodec,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Tasks/Index', [
            'connected' => $request->user()?->hasGoogleTasksConnection() ?? false,
            'pollIntervalMs' => config('google-tasks.poll_interval_ms'),
            'maxBackoffMs' => config('google-tasks.max_backoff_ms'),
        ]);
    }

    public function taskLists(Request $request): JsonResponse
    {
        return $this->run(function () use ($request) {
            $client = new GoogleTasksClient($request->user(), app(GoogleOAuthTokenService::class));

            return response()->json($client->listTaskLists());
        });
    }

    public function search(Request $request, TaskSearcher $searcher): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:200'],
        ]);

        return $this->run(function () use ($request, $searcher, $validated) {
            $client = new GoogleTasksClient($request->user(), app(GoogleOAuthTokenService::class));
            $result = $searcher->search($client, $validated['q']);
            $result['items'] = $this->decodeSearchRows($result['items'] ?? []);

            return response()->json($result);
        });
    }

    public function todayView(Request $request): JsonResponse
    {
        return $this->run(function () use ($request) {
            $client = new GoogleTasksClient($request->user(), app(GoogleOAuthTokenService::class));
            $aggregator = new TaskViewAggregator;

            return response()->json([
                'items' => $this->decodeTodayRows($aggregator->today($client)),
            ]);
        });
    }

    public function inboxView(Request $request): JsonResponse
    {
        return $this->run(function () use ($request) {
            $client = new GoogleTasksClient($request->user(), app(GoogleOAuthTokenService::class));
            $aggregator = new TaskViewAggregator;

            $lists = $client->listTaskLists();
            $listItems = $lists['items'] ?? [];
            $default = $aggregator->resolveDefaultList($listItems);
            if ($default === null) {
                return response()->json([
                    'taskList' => null,
                    'items' => [],
                ]);
            }

            $taskListId = $default['id'] ?? '';
            $tasks = $client->listTasks($taskListId, [
                'showCompleted' => $request->boolean('showCompleted', true),
                'showDeleted' => false,
                'showHidden' => false,
            ]);

            return response()->json([
                'taskList' => $default,
                'items' => $this->decodeTasks($tasks['items'] ?? []),
            ]);
        });
    }

    public function tasks(Request $request, string $taskList): JsonResponse
    {
        return $this->run(function () use ($request, $taskList) {
            $client = new GoogleTasksClient($request->user(), app(GoogleOAuthTokenService::class));

            $payload = $client->listTasks($taskList, [
                'showCompleted' => $request->boolean('showCompleted', true),
                'showDeleted' => $request->boolean('showDeleted', false),
                'showHidden' => $request->boolean('showHidden', false),
            ]);
            $payload['items'] = $this->decodeTasks($payload['items'] ?? []);

            return response()->json($payload);
        });
    }

    public function storeTask(Request $request, string $taskList): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:1024',
            'notes' => 'nullable|string|max:8192',
            'due' => 'nullable|string|max:64',
            'parent' => 'nullable|string|max:256',
            'recurrence' => 'nullable|array',
            'recurrence.*' => 'string|max:512',
            'priority' => 'nullable|in:p1,p2,p3,p4',
        ]);

        return $this->run(function () use ($request, $taskList, $validated) {
            $client = new GoogleTasksClient($request->user(), app(GoogleOAuthTokenService::class));

            $body = [
                'title' => $this->priorityCodec->encodeTitle(
                    $validated['title'],
                    $validated['priority'] ?? null,
                ),
                'status' => 'needsAction',
            ];
            if (! empty($validated['notes'])) {
                $body['notes'] = $validated['notes'];
            }
            if (! empty($validated['due'])) {
                $body['due'] = $validated['due'];
            }
            if (! empty($validated['parent'])) {
                $body['parent'] = $validated['parent'];
            }
            if (! empty($validated['recurrence'])) {
                $body['recurrence'] = $validated['recurrence'];
            }

            $created = $client->insertTask($taskList, $body, []);

            return response()->json($this->priorityCodec->decodeTask($created));
        });
    }

    public function updateTask(Request $request, string $taskList, string $task): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:1024',
            'notes' => 'sometimes|nullable|string|max:8192',
            'due' => 'sometimes|nullable|string|max:64',
            'status' => 'sometimes|nullable|in:needsAction,completed',
            'parent' => 'sometimes|nullable|string|max:256',
            'recurrence' => 'sometimes|nullable|array',
            'recurrence.*' => 'string|max:512',
            'priority' => 'sometimes|nullable|in:p1,p2,p3,p4',
        ]);

        if ($validated === []) {
            return response()->json(['message' => 'No fields to update.'], 422);
        }

        if (array_key_exists('priority', $validated) && ! array_key_exists('title', $validated)) {
            return response()->json(['message' => 'Title is required when updating priority.'], 422);
        }

        return $this->run(function () use ($request, $taskList, $task, $validated) {
            $client = new GoogleTasksClient($request->user(), app(GoogleOAuthTokenService::class));

            $body = [];
            foreach (['title', 'notes', 'due', 'status', 'parent'] as $key) {
                if (array_key_exists($key, $validated) && $validated[$key] !== null) {
                    $body[$key] = $validated[$key];
                }
            }
            if (array_key_exists('priority', $validated)) {
                $body['title'] = $this->priorityCodec->encodeTitle(
                    (string) ($validated['title'] ?? ''),
                    $validated['priority'],
                );
            }
            if (array_key_exists('recurrence', $validated)) {
                $body['recurrence'] = $validated['recurrence'];
            }

            $updated = $client->patchTask($taskList, $task, $body);

            return response()->json($this->priorityCodec->decodeTask($updated));
        });
    }

    public function moveTask(Request $request, string $taskList, string $task): JsonResponse
    {
        $validated = $request->validate([
            'destinationTasklist' => 'required|string|max:256',
        ]);

        return $this->run(function () use ($request, $taskList, $task, $validated) {
            $client = new GoogleTasksClient($request->user(), app(GoogleOAuthTokenService::class));

            $moved = $client->moveTask($taskList, $task, [
                'destinationTasklist' => $validated['destinationTasklist'],
            ]);

            return response()->json($this->priorityCodec->decodeTask($moved));
        });
    }

    public function destroyTask(Request $request, string $taskList, string $task): JsonResponse
    {
        return $this->run(function () use ($request, $taskList, $task) {
            $client = new GoogleTasksClient($request->user(), app(GoogleOAuthTokenService::class));
            $client->deleteTask($taskList, $task);

            return response()->json(['ok' => true]);
        });
    }

    /**
     * @param  \Closure(): (\Illuminate\Http\JsonResponse)  $callback
     */
    private function run(\Closure $callback): JsonResponse
    {
        try {
            return $callback();
        } catch (GoogleTasksRateLimitedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'retry_after' => $e->retryAfterSeconds,
            ], 429);
        } catch (GoogleTasksApiException $e) {
            $status = $e->status >= 400 && $e->status < 600 ? $e->status : 500;

            return response()->json([
                'message' => $e->getMessage(),
            ], $status);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $tasks
     * @return array<int, array<string, mixed>>
     */
    private function decodeTasks(array $tasks): array
    {
        return array_map(
            fn (array $task): array => $this->priorityCodec->decodeTask($task),
            $tasks,
        );
    }

    /**
     * @param  array<int, array{taskListId: string, taskListTitle: string, task: array<string, mixed>}>  $rows
     * @return array<int, array{taskListId: string, taskListTitle: string, task: array<string, mixed>}>
     */
    private function decodeTodayRows(array $rows): array
    {
        return array_map(function (array $row): array {
            $row['task'] = $this->priorityCodec->decodeTask($row['task'] ?? []);

            return $row;
        }, $rows);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function decodeSearchRows(array $rows): array
    {
        return array_map(function (array $row): array {
            $row['task'] = $this->priorityCodec->decodeTask($row['task'] ?? []);

            return $row;
        }, $rows);
    }
}
