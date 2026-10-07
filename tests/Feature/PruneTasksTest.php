<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTasks;
use Tests\TestCase;

class PruneTasksTest extends TestCase
{
    use CreatesTasks, RefreshDatabase;

    public function test_tasks_deleted_more_than_a_week_ago_are_removed_for_good(): void
    {
        $this->pretendNowIs('2026-10-07 12:00:00');

        $user = User::factory()->create();
        $old = $this->makeTask($user);
        $recent = $this->makeTask($user);
        $active = $this->makeTask($user);

        $old->delete();
        $recent->delete();

        Task::withTrashed()->whereKey($old->id)->update(['deleted_at' => now()->subDays(8)]);
        Task::withTrashed()->whereKey($recent->id)->update(['deleted_at' => now()->subDays(2)]);

        $this->artisan('model:prune')->assertSuccessful();

        $this->assertNull(Task::withTrashed()->find($old->id));      // gone for good
        $this->assertNotNull(Task::withTrashed()->find($recent->id)); // still undoable
        $this->assertNotNull(Task::find($active->id));                // never touched
    }
}
