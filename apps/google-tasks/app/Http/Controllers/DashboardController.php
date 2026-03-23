<?php

namespace App\Http\Controllers;

use App\Services\Google\DashboardStatsService;
use App\Services\Google\GoogleOAuthTokenService;
use App\Services\Google\GoogleTasksApiException;
use App\Services\Google\GoogleTasksClient;
use App\Services\Google\GoogleTasksErrorCode;
use App\Services\Google\GoogleTasksRateLimitedException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Dashboard', [
            'hasGoogleTasksConnection' => $user?->hasGoogleTasksConnection() ?? false,
            'userTimezone' => $user?->timezone ?? 'America/Toronto',
        ]);
    }

    public function stats(Request $request, DashboardStatsService $service): JsonResponse
    {
        $validated = $request->validate([
            'range' => ['sometimes', 'integer', 'in:7,14,30,90'],
        ]);

        $rangeDays = (int) ($validated['range'] ?? 30);
        $user = $request->user();

        if (! $user->hasGoogleTasksConnection()) {
            $cached = $service->getCached($user, $rangeDays);
            if ($cached) {
                return response()->json($cached);
            }

            return response()->json([
                'empty' => true,
                'reason' => 'disconnected',
            ]);
        }

        // Cache-first: return cached stats immediately if fresh
        $cached = $service->getCachedIfFresh($user, $rangeDays);
        if ($cached && ! $cached['stale']) {
            return response()->json($cached);
        }

        // Stale cache exists: return it immediately and refresh in the background
        if ($cached && $cached['stale']) {
            defer(function () use ($user, $service, $rangeDays) {
                try {
                    $this->refreshStats($user, $service, $rangeDays);
                } catch (\Throwable $e) {
                    Log::warning('dashboard_deferred_refresh_failed', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });

            return response()->json($cached);
        }

        // No cache: live computation (first visit or new range)
        try {
            return $this->fetchStats($user, $service, $rangeDays);
        } catch (GoogleTasksRateLimitedException $e) {
            Log::warning('dashboard_rate_limited', [
                'user_id' => $user->id,
                'retry_after' => $e->retryAfterSeconds,
            ]);

            $cached = $service->getCached($user, $rangeDays);
            if ($cached) {
                return response()->json([...$cached, 'rateLimited' => true]);
            }

            return response()->json([
                'message' => GoogleTasksErrorCode::RateLimited->userMessage(),
                'code' => GoogleTasksErrorCode::RateLimited->value,
                'retry_after' => $e->retryAfterSeconds,
            ], 429);
        } catch (GoogleTasksApiException $e) {
            Log::warning('dashboard_api_error', [
                'user_id' => $user->id,
                'status' => $e->status,
                'code' => $e->errorCode,
            ]);

            $cached = $service->getCached($user, $rangeDays);
            if ($cached) {
                return response()->json([...$cached, 'apiError' => true]);
            }

            $status = $e->status >= 400 && $e->status < 600 ? $e->status : 500;

            return response()->json([
                'message' => $e->getMessage(),
                'code' => $e->errorCode,
            ], $status);
        }
    }

    private function fetchStats($user, DashboardStatsService $service, int $rangeDays): JsonResponse
    {
        $client = new GoogleTasksClient($user, app(GoogleOAuthTokenService::class));
        $stats = $service->compute($user, $client, $rangeDays);

        return response()->json($stats);
    }

    private function refreshStats($user, DashboardStatsService $service, int $rangeDays): void
    {
        $client = new GoogleTasksClient($user, app(GoogleOAuthTokenService::class));
        $service->compute($user, $client, $rangeDays);
    }
}
