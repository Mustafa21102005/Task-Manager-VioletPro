<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Enums\Recurrence;
use App\Enums\RecurrenceUnit;
use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isCompleted = fake()->boolean(50);
        $dueDate = fake()->optional()->dateTimeBetween('now', '+1 month');

        // Only tasks with a due date can repeat (the form enforces the same rule)
        $recurrence = $dueDate ? fake()->optional(0.25)->randomElement(Recurrence::cases()) : null;
        $isCustom = $recurrence === Recurrence::Custom;

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'due_date' => $dueDate,
            'priority' => fake()->randomElement(Priority::cases()),
            'recurrence' => $recurrence,
            'recurrence_interval' => $isCustom ? 1 : null,
            'recurrence_unit' => $isCustom ? RecurrenceUnit::Week : null,
            'recurrence_days' => $isCustom ? [1, 4] : null,
            'is_completed' => $isCompleted,
            'completed_at' => $isCompleted ? fake()->dateTimeBetween('-30 days', 'now') : null,
            // A completed repeating task has already created its next copy in real use
            'spawned_next' => $isCompleted && $recurrence !== null,
        ];
    }
}
