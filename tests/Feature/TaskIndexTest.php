<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesTasks;
use Tests\TestCase;

class TaskIndexTest extends TestCase
{
    use CreatesTasks, RefreshDatabase;

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get(route('tasks.index'))->assertRedirect(route('login'));
    }

    public function test_it_only_lists_the_users_own_tasks(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->makeTask($user, ['title' => 'Mine']);
        $this->makeTask($other, ['title' => 'Not mine']);

        $this->actingAs($user)->get(route('tasks.index'))
            ->assertInertia(fn(Assert $page) => $page
                ->component('tasks/Index')
                ->has('tasks.data', 1)
                ->where('tasks.data.0.title', 'Mine'));
    }

    public function test_it_filters_by_category(): void
    {
        $user = User::factory()->create();
        $work = $user->categories()->where('name', 'Work')->firstOrFail();
        $personal = $user->categories()->where('name', 'Personal')->firstOrFail();

        $this->makeTask($user, ['title' => 'Work task'], $work);
        $this->makeTask($user, ['title' => 'Personal task'], $personal);

        $this->actingAs($user)->get(route('tasks.index', ['category' => $work->id]))
            ->assertInertia(fn(Assert $page) => $page
                ->has('tasks.data', 1)
                ->where('tasks.data.0.title', 'Work task'));
    }

    public function test_it_filters_by_priority(): void
    {
        $user = User::factory()->create();

        $this->makeTask($user, ['title' => 'Urgent one', 'priority' => 'urgent']);
        $this->makeTask($user, ['title' => 'Low one', 'priority' => 'low']);

        $this->actingAs($user)->get(route('tasks.index', ['priority' => 'urgent']))
            ->assertInertia(fn(Assert $page) => $page
                ->has('tasks.data', 1)
                ->where('tasks.data.0.title', 'Urgent one'));
    }

    public function test_it_can_show_completed_tasks_only(): void
    {
        $user = User::factory()->create();

        $this->makeTask($user, ['title' => 'Open']);
        $this->makeTask($user, ['title' => 'Done', 'is_completed' => true]);

        $this->actingAs($user)->get(route('tasks.index', ['completed' => 'true']))
            ->assertInertia(fn(Assert $page) => $page
                ->has('tasks.data', 1)
                ->where('tasks.data.0.title', 'Done'));
    }

    public function test_it_searches_by_title(): void
    {
        $user = User::factory()->create();

        $this->makeTask($user, ['title' => 'Buy groceries']);
        $this->makeTask($user, ['title' => 'Call the dentist']);

        $this->actingAs($user)->get(route('tasks.index', ['search' => 'grocer']))
            ->assertInertia(fn(Assert $page) => $page
                ->has('tasks.data', 1)
                ->where('tasks.data.0.title', 'Buy groceries'));
    }

    public function test_search_treats_percent_signs_literally(): void
    {
        $user = User::factory()->create();

        $this->makeTask($user, ['title' => '50% off sale']);
        $this->makeTask($user, ['title' => '500 items']);

        $this->actingAs($user)->get(route('tasks.index', ['search' => '50%']))
            ->assertInertia(fn(Assert $page) => $page
                ->has('tasks.data', 1)
                ->where('tasks.data.0.title', '50% off sale'));
    }

    public function test_open_tasks_come_before_completed_ones(): void
    {
        $user = User::factory()->create();

        $this->makeTask($user, ['title' => 'Done', 'is_completed' => true, 'priority' => 'urgent']);
        $this->makeTask($user, ['title' => 'Open', 'priority' => 'low']);

        $this->actingAs($user)->get(route('tasks.index'))
            ->assertInertia(fn(Assert $page) => $page->where('tasks.data.0.title', 'Open'));
    }

    public function test_it_paginates_ten_tasks_per_page(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 12) as $number) {
            $this->makeTask($user, ['title' => "Task {$number}"]);
        }

        $this->actingAs($user)->get(route('tasks.index'))
            ->assertInertia(fn(Assert $page) => $page->has('tasks.data', 10)->where('tasks.total', 12));

        $this->actingAs($user)->get(route('tasks.index', ['page' => 2]))
            ->assertInertia(fn(Assert $page) => $page->has('tasks.data', 2));
    }

    public function test_a_page_past_the_end_redirects_to_the_last_page(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 12) as $number) {
            $this->makeTask($user);
        }

        $this->actingAs($user)->get(route('tasks.index', ['page' => 5]))
            ->assertRedirect(route('tasks.index', ['page' => 2]));
    }
}
