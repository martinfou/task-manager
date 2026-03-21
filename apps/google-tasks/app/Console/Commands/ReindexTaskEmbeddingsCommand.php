<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Google\GoogleOAuthTokenService;
use App\Services\Google\GoogleTasksClient;
use App\Services\Semantic\EmbeddingClient;
use App\Services\Semantic\TaskEmbeddingIndexer;
use Illuminate\Console\Command;

class ReindexTaskEmbeddingsCommand extends Command
{
    protected $signature = 'google-tasks:reindex-embeddings {user? : User ID}';

    protected $description = 'Rebuild semantic search embeddings from Google Tasks (requires OPENAI_API_KEY).';

    public function handle(EmbeddingClient $embeddings, TaskEmbeddingIndexer $indexer): int
    {
        if (! $embeddings->isConfigured()) {
            $this->error('Semantic search is not configured (SEMANTIC_SEARCH_ENABLED + OPENAI_API_KEY).');

            return self::FAILURE;
        }

        $userId = $this->argument('user');
        $user = $userId !== null
            ? User::query()->findOrFail((int) $userId)
            : User::query()->whereNotNull('google_refresh_token')->first();

        if ($user === null) {
            $this->error('No user with Google connection found.');

            return self::FAILURE;
        }

        $client = new GoogleTasksClient($user, app(GoogleOAuthTokenService::class));
        $count = $indexer->reindexUser($user, $client);
        $this->info("Indexed {$count} tasks for user {$user->id}.");

        return self::SUCCESS;
    }
}
