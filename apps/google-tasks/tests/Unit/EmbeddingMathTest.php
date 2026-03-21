<?php

namespace Tests\Unit;

use App\Services\Semantic\EmbeddingMath;
use PHPUnit\Framework\TestCase;

class EmbeddingMathTest extends TestCase
{
    public function test_cosine_of_identical_vectors_is_one(): void
    {
        $v = [1.0, 0.0, 0.0];
        $this->assertEqualsWithDelta(1.0, EmbeddingMath::cosineSimilarity($v, $v), PHP_FLOAT_EPSILON);
    }

    public function test_cosine_of_orthogonal_vectors_is_zero(): void
    {
        $a = [1.0, 0.0];
        $b = [0.0, 1.0];
        $this->assertEqualsWithDelta(0.0, EmbeddingMath::cosineSimilarity($a, $b), PHP_FLOAT_EPSILON);
    }
}
