<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $demo = User::factory()->create([
            'name' => 'User',
            'email' => 'user@gmail.com',
        ]);

        $users = User::factory(4)->create()->prepend($demo);

        $users->each(function (User $user) {
            // The 5 default categories were created by UserObserver
            $categoryIds = $user->categories()->pluck('id');

            Task::factory()
                ->count(20)
                ->for($user)
                ->state(fn() => ['category_id' => $categoryIds->random()])
                ->create();
        });

        // Give the demo user a believable history: tasks completed on each of the last 6 days
        $categoryIds = $demo->categories()->pluck('id');

        foreach (range(0, 5) as $daysAgo) {
            Task::factory()
                ->count(random_int(1, 4))
                ->for($demo)
                ->state(fn() => [
                    'category_id' => $categoryIds->random(),
                    'is_completed' => true,
                    'completed_at' => now()->subDays($daysAgo)->subMinutes(random_int(0, 30)),
                    'recurrence' => null,
                    'spawned_next' => false,
                ])
                ->create();
        }
    }
}
