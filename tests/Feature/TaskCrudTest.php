<?php

namespace Tests\Feature;

use App\Enums\Recurrence;
use App\Enums\RecurrenceUnit;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTasks;
use Tests\TestCase;

class TaskCrudTest extends TestCase
{
    use CreatesTasks, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pretendNowIs('2026-10-07 12:00:00'); // a Wednesday
    }

    private function payload(User $user, array $overrides = []): array
    {
        return array_merge([
            'title' => 'Write report',
            'description' => null,
            'due_date' => '2026-10-10',
            'priority' => 'high',
            'category_id' => $user->categories()->firstOrFail()->id,
            'recurrence' => null,
        ], $overrides);
    }

    // --- Create ---

    public function test_a_task_can_be_created(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tasks.store'), $this->payload($user))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tasks', [
            'user_id' => $user->id,
            'title' => 'Write report',
            'priority' => 'high',
            'due_date' => '2026-10-10',
        ]);
    }

    public function test_a_title_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tasks.store'), $this->payload($user, ['title' => '']))
            ->assertSessionHasErrors('title');

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_a_task_cannot_use_another_users_category(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tasks.store'), $this->payload($user, ['category_id' => $other->categories()->firstOrFail()->id]))
            ->assertSessionHasErrors('category_id');
    }

    public function test_a_new_task_cannot_be_due_in_the_past(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tasks.store'), $this->payload($user, ['due_date' => '2026-10-01']))
            ->assertSessionHasErrors('due_date');
    }

    public function test_a_repeating_task_needs_a_due_date(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tasks.store'), $this->payload($user, ['due_date' => null, 'recurrence' => 'weekly']))
            ->assertSessionHasErrors(['due_date' => 'A repeating task needs a due date.']);
    }

    public function test_a_custom_repeat_rule_is_saved(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tasks.store'), $this->payload($user, [
                'recurrence' => 'custom',
                'recurrence_interval' => 2,
                'recurrence_unit' => 'week',
                'recurrence_days' => [1, 4],
            ]))
            ->assertSessionHasNoErrors();

        $task = Task::firstOrFail();

        $this->assertSame(Recurrence::Custom, $task->recurrence);
        $this->assertSame(2, $task->recurrence_interval);
        $this->assertSame(RecurrenceUnit::Week, $task->recurrence_unit);
        $this->assertSame([1, 4], $task->recurrence_days);
    }

    public function test_a_custom_repeat_needs_a_valid_interval(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tasks.store'), $this->payload($user, [
                'recurrence' => 'custom',
                'recurrence_interval' => 0,
                'recurrence_unit' => 'day',
            ]))
            ->assertSessionHasErrors('recurrence_interval');
    }

    public function test_custom_details_are_dropped_for_a_preset_repeat(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tasks.store'), $this->payload($user, [
                'recurrence' => 'weekly',
                'recurrence_interval' => 5,
                'recurrence_unit' => 'day',
                'recurrence_days' => [2],
            ]))
            ->assertSessionHasNoErrors();

        $task = Task::firstOrFail();

        $this->assertSame(Recurrence::Weekly, $task->recurrence);
        $this->assertNull($task->recurrence_interval);
        $this->assertNull($task->recurrence_unit);
        $this->assertNull($task->recurrence_days);
    }

    // --- Update ---

    public function test_a_task_can_be_updated(): void
    {
        $user = User::factory()->create();
        $task = $this->makeTask($user, ['title' => 'Old title', 'priority' => 'high']);

        $this->actingAs($user)
            ->put(route('tasks.update', $task), $this->payload($user, ['title' => 'New title', 'priority' => 'low']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'New title', 'priority' => 'low']);
    }

    public function test_an_overdue_task_can_still_be_edited(): void
    {
        $user = User::factory()->create();
        $task = $this->makeTask($user, ['due_date' => '2026-10-01']);

        $this->actingAs($user)
            ->put(route('tasks.update', $task), $this->payload($user, ['title' => 'Still saves', 'due_date' => '2026-10-01']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Still saves']);
    }

    public function test_switching_away_from_custom_clears_the_custom_settings(): void
    {
        $user = User::factory()->create();
        $task = $this->makeTask($user, [
            'due_date' => '2026-10-10',
            'recurrence' => 'custom',
            'recurrence_interval' => 2,
            'recurrence_unit' => 'week',
            'recurrence_days' => [1],
        ]);

        $this->actingAs($user)
            ->put(route('tasks.update', $task), $this->payload($user, ['recurrence' => null]))
            ->assertSessionHasNoErrors();

        $task->refresh();

        $this->assertNull($task->recurrence);
        $this->assertNull($task->recurrence_interval);
        $this->assertNull($task->recurrence_unit);
        $this->assertNull($task->recurrence_days);
    }

    public function test_another_users_task_cannot_be_updated(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $task = $this->makeTask($other, ['title' => 'Theirs']);

        $this->actingAs($user)
            ->put(route('tasks.update', $task), $this->payload($user, ['title' => 'Hacked']))
            ->assertForbidden();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Theirs']);
    }

    // --- Delete and undo ---

    public function test_deleting_a_task_hides_it_and_offers_undo(): void
    {
        $user = User::factory()->create();
        $task = $this->makeTask($user);

        $this->actingAs($user)
            ->delete(route('tasks.destroy', $task))
            ->assertSessionHas('success')
            ->assertSessionHas('undo', route('tasks.restore', $task, absolute: false));

        $this->assertSoftDeleted($task);
    }

    public function test_a_deleted_task_can_be_restored(): void
    {
        $user = User::factory()->create();
        $task = $this->makeTask($user);
        $task->delete();

        $this->actingAs($user)
            ->patch(route('tasks.restore', $task))
            ->assertSessionHas('success');

        $this->assertNotSoftDeleted($task);
    }

    public function test_another_users_task_cannot_be_deleted_or_restored(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $kept = $this->makeTask($other);
        $trashed = $this->makeTask($other);
        $trashed->delete();

        $this->actingAs($user)->delete(route('tasks.destroy', $kept))->assertForbidden();
        $this->actingAs($user)->patch(route('tasks.restore', $trashed))->assertForbidden();

        $this->assertNotSoftDeleted($kept);
        $this->assertSoftDeleted($trashed);
    }
}
