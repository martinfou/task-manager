<?php

namespace App\Services\Google;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleTasksClient
{
    public function __construct(
        private readonly User $user,
        private readonly GoogleOAuthTokenService $tokens,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function listTaskLists(): array
    {
        $base = rtrim(config('google-tasks.api_base'), '/');

        return $this->json('get', "{$base}/users/@me/lists", []);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function listTasks(string $taskListId, array $query = []): array
    {
        $base = rtrim(config('google-tasks.api_base'), '/');

        return $this->json('get', "{$base}/lists/{$taskListId}/tasks", $query);
    }

    /**
     * @param  array<string, mixed>  $body
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function insertTask(string $taskListId, array $body, array $query = []): array
    {
        $base = rtrim(config('google-tasks.api_base'), '/');

        return $this->json('post', "{$base}/lists/{$taskListId}/tasks", $query, $body);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function patchTask(string $taskListId, string $taskId, array $body): array
    {
        $base = rtrim(config('google-tasks.api_base'), '/');

        return $this->json('patch', "{$base}/lists/{$taskListId}/tasks/{$taskId}", [], $body);
    }

    public function deleteTask(string $taskListId, string $taskId): void
    {
        $base = rtrim(config('google-tasks.api_base'), '/');

        $this->json('delete', "{$base}/lists/{$taskListId}/tasks/{$taskId}", []);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function moveTask(string $taskListId, string $taskId, array $query = []): array
    {
        $base = rtrim(config('google-tasks.api_base'), '/');

        return $this->json('post', "{$base}/lists/{$taskListId}/tasks/{$taskId}/move", $query, []);
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $json
     * @return array<string, mixed>
     */
    private function json(string $method, string $url, array $query, array $json = []): array
    {
        try {
            return $this->send($method, $url, $query, $json);
        } catch (GoogleTasksApiException $e) {
            if ($e->status === 401) {
                $this->tokens->forgetCachedAccessToken($this->user);

                return $this->send($method, $url, $query, $json);
            }
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $json
     * @return array<string, mixed>
     */
    private function send(string $method, string $url, array $query, array $json = []): array
    {
        $token = $this->tokens->getAccessToken($this->user);

        $pending = Http::withToken($token)
            ->timeout(15)
            ->connectTimeout(5)
            ->acceptJson()
            ->asJson();

        $target = $query === [] ? $url : $url.'?'.http_build_query($query);

        try {
            $response = match ($method) {
                'get' => $pending->get($target),
                'post' => $json === [] ? $pending->withBody('', '')->post($target) : $pending->post($target, $json),
                'patch' => $pending->patch($target, $json),
                'delete' => $pending->delete($target),
                default => throw new GoogleTasksApiException(
                    GoogleTasksErrorCode::Generic->userMessage(),
                    500,
                    GoogleTasksErrorCode::Generic->value,
                ),
            };
        } catch (ConnectionException $e) {
            throw new GoogleTasksApiException(
                GoogleTasksErrorCode::Network->userMessage(),
                503,
                GoogleTasksErrorCode::Network->value,
                $e,
            );
        }

        return $this->decodeResponse($method, $response);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(string $method, Response $response): array
    {
        if ($response->status() === 429) {
            $retryAfter = $response->header('Retry-After');
            $seconds = is_numeric($retryAfter) ? (int) $retryAfter : null;
            throw new GoogleTasksRateLimitedException($seconds);
        }

        if ($response->failed()) {
            $status = $response->status();
            $code = GoogleTasksErrorCode::fromHttpStatus($status);
            $body = $response->json();
            $googleMessage = $body['error']['message'] ?? null;

            Log::warning('google_tasks_api_failed_response', [
                'method' => $method,
                'status' => $status,
                'body' => $body,
            ]);

            throw new GoogleTasksApiException(
                $googleMessage ?? $code->userMessage(),
                $status,
                $code->value,
            );
        }

        if ($method === 'delete') {
            return [];
        }

        $decoded = $response->json();
        if (! is_array($decoded)) {
            return [];
        }

        return $decoded;
    }
}
