<?php

namespace Tests\Feature;

use App\Models\TaskListOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskListOrderTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::factory()->create([
            'google_refresh_token' => 'test-token',
        ]);
    }

    private function fakeLists(array $names): array
    {
        return array_map(fn (string $name) => [
            'id' => 'list_'.strtolower(str_replace(' ', '_', $name)),
            'title' => $name,
        ], $names);
    }

    public function test_auto_pin_initializes_with_in_list_pinned(): void
    {
        $user = $this->makeUser();
        $lists = $this->fakeLists(['Work', 'In', 'Personal']);

        $ordered = TaskListOrder::applyOrder($user->id, $lists);

        $this->assertSame('In', $ordered[0]['title']);
        $this->assertTrue($ordered[0]['pinned']);
        $this->assertFalse($ordered[1]['pinned']);
        $this->assertFalse($ordered[2]['pinned']);
    }

    public function test_auto_pin_falls_back_to_first_list(): void
    {
        $user = $this->makeUser();
        $lists = $this->fakeLists(['Work', 'Personal']);

        $ordered = TaskListOrder::applyOrder($user->id, $lists);

        $this->assertSame('Work', $ordered[0]['title']);
        $this->assertTrue($ordered[0]['pinned']);
    }

    public function test_save_order_persists_positions(): void
    {
        $user = $this->makeUser();

        TaskListOrder::saveOrder($user->id, [
            ['id' => 'list_a', 'pinned' => true],
            ['id' => 'list_b', 'pinned' => false],
            ['id' => 'list_c', 'pinned' => false],
        ]);

        $entries = TaskListOrder::getOrderedEntries($user->id);

        $this->assertCount(3, $entries);
        $this->assertSame('list_a', $entries[0]['google_list_id']);
        $this->assertTrue($entries[0]['pinned']);
        $this->assertSame('list_b', $entries[1]['google_list_id']);
        $this->assertFalse($entries[1]['pinned']);
    }

    public function test_new_list_appended_to_bottom(): void
    {
        $user = $this->makeUser();
        $lists = $this->fakeLists(['In', 'Work']);
        TaskListOrder::applyOrder($user->id, $lists);

        // Now Google returns a third list
        $lists = $this->fakeLists(['In', 'Work', 'New Project']);
        $ordered = TaskListOrder::applyOrder($user->id, $lists);

        $this->assertSame('In', $ordered[0]['title']);
        $this->assertSame('Work', $ordered[1]['title']);
        $this->assertSame('New Project', $ordered[2]['title']);
    }

    public function test_deleted_list_cleaned_up(): void
    {
        $user = $this->makeUser();
        $lists = $this->fakeLists(['In', 'Work', 'Old']);
        TaskListOrder::applyOrder($user->id, $lists);

        $this->assertSame(3, TaskListOrder::where('user_id', $user->id)->count());

        // Google no longer returns "Old"
        $lists = $this->fakeLists(['In', 'Work']);
        TaskListOrder::applyOrder($user->id, $lists);

        $this->assertSame(2, TaskListOrder::where('user_id', $user->id)->count());
    }

    public function test_auto_sort_orders_unpinned_alphabetically(): void
    {
        $user = $this->makeUser();
        $lists = $this->fakeLists(['In', 'Zebra', 'Alpha', 'Middle']);

        $ordered = TaskListOrder::applyOrder($user->id, $lists, 'alpha_asc');

        $this->assertSame('In', $ordered[0]['title']);
        $this->assertTrue($ordered[0]['pinned']);
        // Unpinned should be alphabetical
        $this->assertSame('Alpha', $ordered[1]['title']);
        $this->assertSame('Middle', $ordered[2]['title']);
        $this->assertSame('Zebra', $ordered[3]['title']);
    }

    public function test_auto_sort_desc(): void
    {
        $user = $this->makeUser();
        $lists = $this->fakeLists(['In', 'Alpha', 'Zebra']);

        $ordered = TaskListOrder::applyOrder($user->id, $lists, 'alpha_desc');

        $this->assertSame('In', $ordered[0]['title']);
        $this->assertSame('Zebra', $ordered[1]['title']);
        $this->assertSame('Alpha', $ordered[2]['title']);
    }

    public function test_toggle_pin(): void
    {
        $user = $this->makeUser();
        $lists = $this->fakeLists(['In', 'Work']);
        TaskListOrder::applyOrder($user->id, $lists);

        // Pin "Work"
        $pinned = TaskListOrder::togglePin($user->id, 'list_work');
        $this->assertTrue($pinned);

        $entry = TaskListOrder::where('user_id', $user->id)
            ->where('google_list_id', 'list_work')
            ->first();
        $this->assertTrue($entry->pinned);

        // Unpin "Work"
        $pinned = TaskListOrder::togglePin($user->id, 'list_work');
        $this->assertFalse($pinned);
    }

    public function test_cross_user_isolation(): void
    {
        $user1 = $this->makeUser();
        $user2 = $this->makeUser();

        TaskListOrder::saveOrder($user1->id, [
            ['id' => 'list_a', 'pinned' => true],
        ]);
        TaskListOrder::saveOrder($user2->id, [
            ['id' => 'list_b', 'pinned' => false],
        ]);

        $entries1 = TaskListOrder::getOrderedEntries($user1->id);
        $entries2 = TaskListOrder::getOrderedEntries($user2->id);

        $this->assertCount(1, $entries1);
        $this->assertSame('list_a', $entries1[0]['google_list_id']);
        $this->assertCount(1, $entries2);
        $this->assertSame('list_b', $entries2[0]['google_list_id']);
    }

    public function test_purge_for_user(): void
    {
        $user = $this->makeUser();
        TaskListOrder::saveOrder($user->id, [
            ['id' => 'list_a', 'pinned' => true],
            ['id' => 'list_b', 'pinned' => false],
        ]);

        TaskListOrder::purgeForUser($user->id);

        $this->assertSame(0, TaskListOrder::where('user_id', $user->id)->count());
    }

    public function test_empty_lists_returns_empty(): void
    {
        $user = $this->makeUser();

        $ordered = TaskListOrder::applyOrder($user->id, []);

        $this->assertSame([], $ordered);
    }
}
