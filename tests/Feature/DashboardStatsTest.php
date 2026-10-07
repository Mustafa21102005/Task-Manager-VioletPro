<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesTasks;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use CreatesTasks, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pretendNowIs('2026-10-07 12:00:00'); // Wednesday; this week is Oct 5 to Oct 11
    }

    private function seedMixedTasks(User $user): void
    {
        $this->makeTask($user, ['title' => 'Today A', 'due_date' => '2026-10-07']);
        $this->makeTask($user, ['title' => 'Today B', 'due_date' => '2026-10-07']);
        $this->makeTask($user, ['title' => 'Done today', 'due_date' => '2026-10-07', 'is_completed' => true, 'completed_at' => now()]);
        $this->makeTask($user, ['title' => 'Overdue', 'due_date' => '2026-10-03']);
        $this->makeTask($user, ['title' => 'This week', 'due_date' => '2026-10-09']);
        $this->makeTask($user, ['title' => 'Later this month', 'due_date' => '2026-10-20']);
        $this->makeTask($user, ['title' => 'Next month', 'due_date' => '2026-11-02']);
        $this->makeTask($user, ['title' => 'Someday']);
    }

    public function test_the_counters_only_count_open_tasks_where_they_should(): void
    {
        $user = User::factory()->create();
        $this->seedMixedTasks($user);
        $this->makeTask(User::factory()->create(), ['due_date' => '2026-10-07']); // someone else's

        $this->actingAs($user)->get(route('dashboard'))
            ->assertInertia(fn(Assert $page) => $page
                ->component('Dashboard')
                ->where('stats.total', 8)
                ->where('stats.completed', 1)
                ->where('stats.open', 7)
                ->where('stats.due_today', 2) // the completed one is not counted
                ->where('stats.overdue', 1));
    }

    public function test_progress_is_calculated_for_today_this_week_and_this_month(): void
    {
        $user = User::factory()->create();
        $this->seedMixedTasks($user);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertInertia(fn(Assert $page) => $page
                ->where('progress.today.total', 3)
                ->where('progress.today.completed', 1)
                ->where('progress.today.percent', 33)
                ->where('progress.week.total', 4)
                ->where('progress.week.completed', 1)
                ->where('progress.week.percent', 25)
                ->where('progress.month.total', 6)
                ->where('progress.month.completed', 1)
                ->where('progress.month.percent', 17));
    }

    public function test_progress_is_zero_when_nothing_is_due(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertInertia(fn(Assert $page) => $page
                ->where('progress.today.total', 0)
                ->where('progress.today.percent', 0));
    }

    public function test_the_focus_list_shows_overdue_first_and_skips_completed_tasks(): void
    {
        $user = User::factory()->create();
        $this->seedMixedTasks($user);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertInertia(fn(Assert $page) => $page
                ->has('focusTasks', 5)
                ->where('focusTasks.0.title', 'Overdue')
                ->where('focusTasks.0.due_status', 'overdue')
                ->where('focusTasks.1.title', 'Today A')
                ->where('focusTasks.1.due_status', 'today'));
    }

    public function test_the_streak_counts_consecutive_days_ending_today(): void
    {
        $user = User::factory()->create();

        foreach (['2026-10-07 09:00:00', '2026-10-06 09:00:00', '2026-10-05 09:00:00', '2026-10-02 09:00:00'] as $moment) {
            $this->makeTask($user, ['is_completed' => true, 'completed_at' => $moment]);
        }

        $this->actingAs($user)->get(route('dashboard'))
            ->assertInertia(fn(Assert $page) => $page
                ->where('activity.current_streak', 3)
                ->where('activity.best_streak', 3)
                ->where('activity.completed_today', 1)
                ->where('activity.this_week', 3)
                ->where('activity.last_week', 0)
                ->has('activity.week', 7)
                ->where('activity.week.2.date', '2026-10-07')
                ->where('activity.week.2.is_today', true)
                ->where('activity.week.2.count', 1));
    }

    public function test_the_streak_survives_a_day_with_nothing_done_yet(): void
    {
        $user = User::factory()->create();

        $this->makeTask($user, ['is_completed' => true, 'completed_at' => '2026-10-06 09:00:00']);
        $this->makeTask($user, ['is_completed' => true, 'completed_at' => '2026-10-05 09:00:00']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertInertia(fn(Assert $page) => $page
                ->where('activity.current_streak', 2)
                ->where('activity.completed_today', 0));
    }

    public function test_the_streak_breaks_after_a_missed_day(): void
    {
        $user = User::factory()->create();

        $this->makeTask($user, ['is_completed' => true, 'completed_at' => '2026-10-05 09:00:00']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertInertia(fn(Assert $page) => $page
                ->where('activity.current_streak', 0)
                ->where('activity.best_streak', 1));
    }

    public function test_other_users_completions_do_not_count(): void
    {
        $user = User::factory()->create();
        $this->makeTask(User::factory()->create(), ['is_completed' => true, 'completed_at' => now()]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertInertia(fn(Assert $page) => $page->where('activity.completed_today', 0));
    }
}
