<?php

namespace Tests\Feature;

use App\Enums\Recurrence;
use App\Enums\RecurrenceUnit;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTasks;
use Tests\TestCase;

class ToggleCompleteTest extends TestCase
{
    use CreatesTasks, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pretendNowIs('2026-10-05 09:00:00'); // a Monday
    }

    public function test_completing_a_task_records_when(): void
    {
        $user = User::factory()->create();
        $task = $this->makeTask($user);

        $this->actingAs($user)->patch(route('tasks.toggle-complete', $task))->assertSessionHas('success');

        $task->refresh();

        $this->assertTrue($task->is_completed);
        $this->assertSame('2026-10-05 09:00:00', $task->completed_at->toDateTimeString());
    }

    public function test_uncompleting_a_task_clears_the_completion_time(): void
    {
        $user = User::factory()->create();
        $task = $this->makeTask($user, ['is_completed' => true, 'completed_at' => now()]);

        $this->actingAs($user)->patch(route('tasks.toggle-complete', $task));

        $task->refresh();

        $this->assertFalse($task->is_completed);
        $this->assertNull($task->completed_at);
    }

    public function test_a_normal_task_does_not_create_a_follow_up(): void
    {
        $user = User::factory()->create();
        $task = $this->makeTask($user, ['due_date' => '2026-10-05']);

        $this->actingAs($user)->patch(route('tasks.toggle-complete', $task));

        $this->assertSame(1, Task::where('user_id', $user->id)->count());
    }

    public function test_completing_a_weekly_task_creates_next_weeks_copy(): void
    {
        $user = User::factory()->create();
        $task = $this->makeTask($user, ['title' => 'Water plants', 'priority' => 'high', 'due_date' => '2026-10-05', 'recurrence' => 'weekly']);

        $this->actingAs($user)->patch(route('tasks.toggle-complete', $task))->assertSessionHas('success');

        $next = Task::where('user_id', $user->id)->whereKeyNot($task->id)->firstOrFail();

        $this->assertSame('2026-10-12', $next->due_date->toDateString());
        $this->assertSame('Water plants', $next->title);
        $this->assertSame('high', $next->priority->value);
        $this->assertSame($task->category_id, $next->category_id);
        $this->assertSame(Recurrence::Weekly, $next->recurrence);
        $this->assertFalse($next->is_completed);
        $this->assertTrue($task->fresh()->spawned_next);
    }

    public function test_checking_the_same_task_again_does_not_create_a_second_copy(): void
    {
        $user = User::factory()->create();
        $task = $this->makeTask($user, ['due_date' => '2026-10-05', 'recurrence' => 'weekly']);

        $this->actingAs($user)->patch(route('tasks.toggle-complete', $task)); // complete
        $this->actingAs($user)->patch(route('tasks.toggle-complete', $task)); // undo
        $this->actingAs($user)->patch(route('tasks.toggle-complete', $task)); // complete again

        $this->assertSame(2, Task::where('user_id', $user->id)->count());
    }

    public function test_the_custom_rule_is_copied_to_the_next_task(): void
    {
        $user = User::factory()->create();
        $task = $this->makeTask($user, [
            'due_date' => '2026-10-05',
            'recurrence' => 'custom',
            'recurrence_interval' => 1,
            'recurrence_unit' => 'week',
            'recurrence_days' => [1, 4],
        ]);

        $this->actingAs($user)->patch(route('tasks.toggle-complete', $task));

        $next = Task::where('user_id', $user->id)->whereKeyNot($task->id)->firstOrFail();

        $this->assertSame('2026-10-08', $next->due_date->toDateString()); // the Thursday
        $this->assertSame(Recurrence::Custom, $next->recurrence);
        $this->assertSame(RecurrenceUnit::Week, $next->recurrence_unit);
        $this->assertSame([1, 4], $next->recurrence_days);
    }

    public function test_another_users_task_cannot_be_toggled(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $task = $this->makeTask($other);

        $this->actingAs($user)->patch(route('tasks.toggle-complete', $task))->assertForbidden();

        $this->assertFalse($task->fresh()->is_completed);
    }
}
