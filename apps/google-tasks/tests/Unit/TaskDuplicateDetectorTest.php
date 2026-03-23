<?php

namespace Tests\Unit;

use App\Models\TaskEmbedding;
use App\Models\User;
use App\Services\Semantic\TaskDuplicateDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskDuplicateDetectorTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::factory()->create();
    }

    private function makeEmbedding(User $user, array $attrs): TaskEmbedding
    {
        return TaskEmbedding::create(array_merge([
            'user_id' => $user->id,
            'task_list_id' => 'list1',
            'task_id' => 'task_'.uniqid(),
            'content_hash' => sha1(uniqid()),
            'dimensions' => 3,
            'embedding' => [1.0, 0.0, 0.0],
            'task_list_title' => 'My Tasks',
            'title_raw' => 'Test task',
            'notes_raw' => null,
            'status' => 'needsAction',
        ], $attrs));
    }

    public function test_returns_empty_when_no_embeddings(): void
    {
        $user = $this->makeUser();
        $detector = new TaskDuplicateDetector;

        $result = $detector->detect($user);

        $this->assertEmpty($result['pairs']);
        $this->assertTrue($result['index_empty']);
    }

    public function test_returns_empty_for_single_task(): void
    {
        $user = $this->makeUser();
        $this->makeEmbedding($user, []);
        $detector = new TaskDuplicateDetector;

        $result = $detector->detect($user);

        $this->assertEmpty($result['pairs']);
        $this->assertFalse($result['index_empty']);
    }

    public function test_detects_identical_embeddings_as_duplicates(): void
    {
        $user = $this->makeUser();
        $this->makeEmbedding($user, [
            'task_id' => 'a',
            'title_raw' => 'Buy groceries',
            'embedding' => [1.0, 0.0, 0.0],
        ]);
        $this->makeEmbedding($user, [
            'task_id' => 'b',
            'title_raw' => 'Get food from store',
            'embedding' => [1.0, 0.0, 0.0],
        ]);

        $detector = new TaskDuplicateDetector;
        $result = $detector->detect($user, 0.8);

        $this->assertCount(1, $result['pairs']);
        $this->assertEqualsWithDelta(1.0, $result['pairs'][0]['score'], 0.01);
    }

    public function test_excludes_completed_tasks(): void
    {
        $user = $this->makeUser();
        $this->makeEmbedding($user, [
            'task_id' => 'a',
            'embedding' => [1.0, 0.0, 0.0],
            'status' => 'needsAction',
        ]);
        $this->makeEmbedding($user, [
            'task_id' => 'b',
            'embedding' => [1.0, 0.0, 0.0],
            'status' => 'completed',
        ]);

        $detector = new TaskDuplicateDetector;
        $result = $detector->detect($user, 0.8);

        $this->assertEmpty($result['pairs']);
    }

    public function test_orthogonal_vectors_not_duplicates(): void
    {
        $user = $this->makeUser();
        $this->makeEmbedding($user, [
            'task_id' => 'a',
            'embedding' => [1.0, 0.0, 0.0],
        ]);
        $this->makeEmbedding($user, [
            'task_id' => 'b',
            'embedding' => [0.0, 1.0, 0.0],
        ]);

        $detector = new TaskDuplicateDetector;
        $result = $detector->detect($user, 0.5);

        $this->assertEmpty($result['pairs']);
    }

    public function test_keeper_hint_favors_notes(): void
    {
        $user = $this->makeUser();
        $this->makeEmbedding($user, [
            'task_id' => 'a',
            'title_raw' => 'Write report',
            'notes_raw' => 'Detailed notes about the report requirements and deadlines.',
            'embedding' => [0.9, 0.1, 0.0],
        ]);
        $this->makeEmbedding($user, [
            'task_id' => 'b',
            'title_raw' => 'Draft report',
            'notes_raw' => null,
            'embedding' => [0.9, 0.1, 0.0],
        ]);

        $detector = new TaskDuplicateDetector;
        $result = $detector->detect($user, 0.8);

        $this->assertCount(1, $result['pairs']);
        $this->assertEquals('A', $result['pairs'][0]['keeperHint']);
    }

    public function test_no_keeper_hint_when_both_empty_notes(): void
    {
        $user = $this->makeUser();
        $this->makeEmbedding($user, [
            'task_id' => 'a',
            'embedding' => [1.0, 0.0, 0.0],
            'notes_raw' => null,
        ]);
        $this->makeEmbedding($user, [
            'task_id' => 'b',
            'embedding' => [1.0, 0.0, 0.0],
            'notes_raw' => null,
        ]);

        $detector = new TaskDuplicateDetector;
        $result = $detector->detect($user, 0.8);

        $this->assertCount(1, $result['pairs']);
        $this->assertNull($result['pairs'][0]['keeperHint']);
    }

    public function test_does_not_cross_users(): void
    {
        $user1 = $this->makeUser();
        $user2 = $this->makeUser();
        $this->makeEmbedding($user1, [
            'task_id' => 'a',
            'embedding' => [1.0, 0.0, 0.0],
        ]);
        $this->makeEmbedding($user2, [
            'task_id' => 'b',
            'embedding' => [1.0, 0.0, 0.0],
        ]);

        $detector = new TaskDuplicateDetector;
        $result = $detector->detect($user1, 0.8);

        $this->assertEmpty($result['pairs']);
    }
}
