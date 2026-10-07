<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesTasks;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use CreatesTasks, RefreshDatabase;

    public function test_it_lists_only_the_users_own_categories(): void
    {
        $user = User::factory()->create();
        User::factory()->create(); // someone else, with their own 5 categories

        $this->actingAs($user)->get(route('categories.index'))
            ->assertInertia(fn(Assert $page) => $page
                ->component('categories/Index')
                ->has('categories.data', 5));
    }

    public function test_a_category_can_be_created(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('categories.store'), ['name' => 'Study'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', ['user_id' => $user->id, 'name' => 'Study']);
    }

    public function test_names_must_be_unique_per_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('categories.store'), ['name' => 'Work']) // one of the 5 defaults
            ->assertSessionHasErrors('name');
    }

    public function test_two_users_can_use_the_same_new_name(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->actingAs($first)->post(route('categories.store'), ['name' => 'Study'])->assertSessionHasNoErrors();
        $this->actingAs($second)->post(route('categories.store'), ['name' => 'Study'])->assertSessionHasNoErrors();

        $this->assertSame(2, Category::where('name', 'Study')->count());
    }

    public function test_a_category_can_be_renamed(): void
    {
        $user = User::factory()->create();
        $category = $user->categories()->where('name', 'Work')->firstOrFail();

        $this->actingAs($user)
            ->put(route('categories.update', $category), ['name' => 'Deep work'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Deep work']);
    }

    public function test_saving_a_category_without_changing_its_name_is_allowed(): void
    {
        $user = User::factory()->create();
        $category = $user->categories()->where('name', 'Work')->firstOrFail();

        $this->actingAs($user)
            ->put(route('categories.update', $category), ['name' => 'Work'])
            ->assertSessionHasNoErrors();
    }

    public function test_a_category_cannot_be_renamed_to_an_existing_one(): void
    {
        $user = User::factory()->create();
        $category = $user->categories()->where('name', 'Work')->firstOrFail();

        $this->actingAs($user)
            ->put(route('categories.update', $category), ['name' => 'Personal'])
            ->assertSessionHasErrors('name');
    }

    public function test_another_users_category_cannot_be_changed_or_deleted(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = $other->categories()->firstOrFail();

        $this->actingAs($user)->put(route('categories.update', $category), ['name' => 'Hacked'])->assertForbidden();
        $this->actingAs($user)->delete(route('categories.destroy', $category))->assertForbidden();

        $this->assertModelExists($category);
    }

    public function test_a_category_with_tasks_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $category = $user->categories()->where('name', 'Work')->firstOrFail();
        $this->makeTask($user, [], $category);

        $this->actingAs($user)
            ->delete(route('categories.destroy', $category))
            ->assertSessionHas('error');

        $this->assertModelExists($category);
    }

    public function test_an_empty_category_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $category = $user->categories()->where('name', 'Work')->firstOrFail();

        $this->actingAs($user)
            ->delete(route('categories.destroy', $category))
            ->assertSessionHas('success');

        $this->assertModelMissing($category);
    }

    public function test_deleting_a_category_also_removes_its_already_deleted_tasks(): void
    {
        $user = User::factory()->create();
        $category = $user->categories()->where('name', 'Work')->firstOrFail();

        $this->makeTask($user, [], $category)->delete(); // soft-deleted, so not "a task" for the guard

        $this->actingAs($user)
            ->delete(route('categories.destroy', $category))
            ->assertSessionHas('success');

        $this->assertModelMissing($category);
        $this->assertSame(0, Task::withTrashed()->count());
    }
}
