<?php

namespace App\Services\Semantic;

final class EmbeddingMath
{
    /**
     * @param  array<int, float>  $a
     * @param  array<int, float>  $b
     */
    public static function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b) || $a === []) {
            return 0.0;
        }

        $dot = 0.0;
        $na = 0.0;
        $nb = 0.0;
        foreach ($a as $i => $va) {
            $vb = $b[$i];
            $dot += $va * $vb;
            $na += $va * $va;
            $nb += $vb * $vb;
        }

        $den = sqrt($na) * sqrt($nb);

        return $den > 0.0 ? $dot / $den : 0.0;
    }
}
