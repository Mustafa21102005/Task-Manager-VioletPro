<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesTasks;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use CreatesTasks, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // October 2026 starts on a Thursday, so the grid runs Mon Sep 28 to Sun Nov 1 (35 days)
        $this->pretendNowIs('2026-10-07 12:00:00');
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get(route('calendar.index'))->assertRedirect(route('login'));
    }

    public function test_it_shows_the_current_month_by_default(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('calendar.index'))
            ->assertInertia(fn(Assert $page) => $page
                ->component('calendar/Index')
                ->where('month', '2026-10')
                ->where('prevMonth', '2026-09')
                ->where('nextMonth', '2026-11')
                ->where('today', '2026-10-07')
                ->has('days', 35)
                ->where('days.0.date', '2026-09-28'));
    }

    public function test_the_days_are_flagged_correctly(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('calendar.index'))
            ->assertInertia(fn(Assert $page) => $page
                ->where('days.2.date', '2026-09-30')
                ->where('days.2.in_month', false)
                ->where('days.3.date', '2026-10-01')
                ->where('days.3.in_month', true)
                ->where('days.8.date', '2026-10-06')
                ->where('days.8.is_past', true)
                ->where('days.9.date', '2026-10-07')
                ->where('days.9.is_today', true)
                ->where('days.9.is_past', false)
                ->where('days.10.is_past', false));
    }

    public function test_it_includes_tasks_from_the_visible_grid_only(): void
    {
        $user = User::factory()->create();

        $this->makeTask($user, ['title' => 'Edge of grid', 'due_date' => '2026-09-30']);
        $this->makeTask($user, ['title' => 'Mid month', 'due_date' => '2026-10-15']);
        $this->makeTask($user, ['title' => 'Last grid day', 'due_date' => '2026-11-01']);
        $this->makeTask($user, ['title' => 'Before the grid', 'due_date' => '2026-09-27']);
        $this->makeTask($user, ['title' => 'After the grid', 'due_date' => '2026-11-02']);
        $this->makeTask($user, ['title' => 'No date']);
        $this->makeTask(User::factory()->create(), ['title' => 'Not mine', 'due_date' => '2026-10-15']);

        $this->actingAs($user)->get(route('calendar.index'))
            ->assertInertia(fn(Assert $page) => $page->has('tasks', 3));
    }

    public function test_it_can_show_another_month(): void
    {
        $user = User::factory()->create();

        $this->makeTask($user, ['title' => 'In December', 'due_date' => '2026-12-15']);
        $this->makeTask($user, ['title' => 'In October', 'due_date' => '2026-10-15']);

        $this->actingAs($user)->get(route('calendar.index', ['month' => '2026-12']))
            ->assertInertia(fn(Assert $page) => $page
                ->where('month', '2026-12')
                ->where('prevMonth', '2026-11')
                ->where('nextMonth', '2027-01')
                ->has('tasks', 1)
                ->where('tasks.0.title', 'In December'));
    }

    public function test_an_invalid_month_falls_back_to_the_current_one(): void
    {
        $user = User::factory()->create();

        foreach (['banana', '2026-13', '26-10'] as $invalid) {
            $this->actingAs($user)->get(route('calendar.index', ['month' => $invalid]))
                ->assertOk()
                ->assertInertia(fn(Assert $page) => $page->where('month', '2026-10'));
        }
    }
}
