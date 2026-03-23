<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Google\DashboardStatsService;
use App\Services\Google\GoogleOAuthTokenService;
use App\Services\Google\GoogleTasksClient;
use App\Services\Google\GoogleTasksRateLimitedException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshDashboardStats extends Command
{
    protected $signature = 'dashboard:refresh-stats
        {user? : Specific user ID to refresh (omit for all connected users)}
        {--ranges=7,14,30,90 : Comma-separated range values to compute}';

    protected $description = 'Pre-compute and cache dashboard stats for users with active Google Tasks connections.';

    public function handle(DashboardStatsService $service): int
    {
        $ranges = array_map('intval', explode(',', $this->option('ranges')));

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

                foreach ($ranges as $range) {
                    $service->compute($user, $client, $range);
                }

                $refreshed++;
                $this->line("  ✓ User {$user->id}: refreshed ".count($ranges).' ranges');
            } catch (GoogleTasksRateLimitedException $e) {
                $skipped++;
                $this->warn("  ⚠ User {$user->id}: rate-limited (retry after {$e->retryAfterSeconds}s)");
                Log::warning('dashboard_refresh_rate_limited', [
                    'user_id' => $user->id,
                    'retry_after' => $e->retryAfterSeconds,
                ]);
            } catch (\Throwable $e) {
                $skipped++;
                $this->warn("  ⚠ User {$user->id}: {$e->getMessage()}");
                Log::warning('dashboard_refresh_failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Done: {$refreshed} refreshed, {$skipped} skipped.");

        return self::SUCCESS;
    }
}
