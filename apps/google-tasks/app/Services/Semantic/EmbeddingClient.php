<?php

namespace App\Services\Semantic;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class EmbeddingClient
{
    public function isConfigured(): bool
    {
        if (! config('google-tasks.semantic_search.enabled')) {
            return false;
        }

        return filled(config('google-tasks.semantic_search.openai_api_key'));
    }

    /**
     * @param  array<int, string>  $inputs
     * @return array<int, array<int, float>>
     */
    public function embedBatch(array $inputs): array
    {
        if ($inputs === []) {
            return [];
        }

        $apiKey = (string) config('google-tasks.semantic_search.openai_api_key');
        $model = (string) config('google-tasks.semantic_search.openai_model');
        $url = (string) config('google-tasks.semantic_search.openai_url');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
        ])->timeout(120)->asJson()->post($url, [
            'model' => $model,
            'input' => $inputs,
        ]);

        $this->throwIfFailed($response);

        $data = $response->json('data');
        if (! is_array($data)) {
            throw new RuntimeException('OpenAI embeddings response missing data array.');
        }

        $vectors = [];
        foreach ($data as $row) {
            if (! is_array($row) || ! isset($row['embedding']) || ! is_array($row['embedding'])) {
                continue;
            }
            $vectors[] = array_map(static fn ($v): float => (float) $v, $row['embedding']);
        }

        if (count($vectors) !== count($inputs)) {
            throw new RuntimeException('OpenAI embeddings count mismatch.');
        }

        return $vectors;
    }

    /**
     * @return array<int, float>
     */
    public function embedOne(string $text): array
    {
        $batch = $this->embedBatch([$text]);

        return $batch[0] ?? [];
    }

    private function throwIfFailed(Response $response): void
    {
        if ($response->failed()) {
            throw new RuntimeException(
                'Embedding request failed: '.$response->body(),
                $response->status(),
            );
        }
    }
}
