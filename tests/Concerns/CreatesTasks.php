<?php

namespace Tests\Concerns;

use App\Models\Category;
use App\Models\Task;
use App\Models\User;

trait CreatesTasks
{
    /**
     * Create a task with every random factory value fixed. Pass attributes to override them.
     */
    protected function makeTask(User $user, array $attributes = [], ?Category $category = null): Task
    {
        return Task::factory()
            ->for($user)
            ->for($category ?? $user->categories()->firstOrFail())
            ->create(array_merge([
                'priority' => 'medium',
                'due_date' => null,
                'is_completed' => false,
                'completed_at' => null,
                'recurrence' => null,
                'recurrence_interval' => null,
                'recurrence_unit' => null,
                'recurrence_days' => null,
                'spawned_next' => false,
            ], $attributes));
    }
}
