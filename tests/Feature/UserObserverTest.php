<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_user_gets_the_five_default_categories(): void
    {
        $user = User::factory()->create();

        $this->assertEqualsCanonicalizing(
            ['General', 'Work', 'Personal', 'Meeting', 'Follow-up'],
            $user->categories()->pluck('name')->all(),
        );
    }

    public function test_each_user_gets_their_own_set(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->assertSame(5, $first->categories()->count());
        $this->assertSame(5, $second->categories()->count());
        $this->assertEmpty($first->categories()->pluck('id')->intersect($second->categories()->pluck('id')));
    }
}
