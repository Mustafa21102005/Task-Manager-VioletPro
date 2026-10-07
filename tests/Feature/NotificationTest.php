<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\DueTasksReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesTasks;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use CreatesTasks, RefreshDatabase;

    // --- The daily reminder command ---

    public function test_it_sends_one_reminder_to_users_with_tasks_due_or_overdue(): void
    {
        $this->pretendNowIs('2026-10-07 08:00:00');

        $user = User::factory()->create();
        $this->makeTask($user, ['due_date' => '2026-10-07']); // due today
        $this->makeTask($user, ['due_date' => '2026-10-01']); // overdue

        $quiet = User::factory()->create();
        $this->makeTask($quiet, ['due_date' => '2026-10-20']); // nothing due yet

        $this->artisan('reminders:send')->assertSuccessful();

        $this->assertSame(1, $user->notifications()->count());
        $this->assertSame('You have 1 task due today and 1 overdue.', $user->notifications()->first()->data['message']);
        $this->assertSame(0, $quiet->notifications()->count());
    }

    public function test_completed_tasks_do_not_trigger_a_reminder(): void
    {
        $this->pretendNowIs('2026-10-07 08:00:00');

        $user = User::factory()->create();
        $this->makeTask($user, ['due_date' => '2026-10-07', 'is_completed' => true]);

        $this->artisan('reminders:send')->assertSuccessful();

        $this->assertSame(0, $user->notifications()->count());
    }

    public function test_running_the_command_twice_in_a_day_sends_only_one_reminder(): void
    {
        $this->pretendNowIs('2026-10-07 08:00:00');

        $user = User::factory()->create();
        $this->makeTask($user, ['due_date' => '2026-10-07']);

        $this->artisan('reminders:send');
        $this->artisan('reminders:send');

        $this->assertSame(1, $user->notifications()->count());
    }

    public function test_the_next_day_gets_a_new_reminder(): void
    {
        $user = User::factory()->create();
        $this->makeTask($user, ['due_date' => '2026-10-07']);

        $this->pretendNowIs('2026-10-07 08:00:00');
        $this->artisan('reminders:send');

        $this->pretendNowIs('2026-10-08 08:00:00');
        $this->artisan('reminders:send');

        $this->assertSame(2, $user->notifications()->count());
    }

    // --- Reading and marking notifications ---

    public function test_unread_notifications_are_shared_with_every_page(): void
    {
        $user = User::factory()->create();
        $user->notify(new DueTasksReminder(1, 0));
        $user->notify(new DueTasksReminder(0, 2));

        $this->actingAs($user)->get(route('dashboard'))
            ->assertInertia(fn(Assert $page) => $page
                ->where('notifications.unread_count', 2)
                ->has('notifications.items', 2)
                ->where('notifications.items.0.read', false));
    }

    public function test_a_notification_can_be_marked_as_read(): void
    {
        $user = User::factory()->create();
        $user->notify(new DueTasksReminder(1, 0));
        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($user)->post(route('notifications.read', $notification->id))->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_all_notifications_can_be_marked_as_read(): void
    {
        $user = User::factory()->create();
        $user->notify(new DueTasksReminder(1, 0));
        $user->notify(new DueTasksReminder(2, 0));

        $this->actingAs($user)->post(route('notifications.read-all'))->assertRedirect();

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_another_users_notification_cannot_be_marked_as_read(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $other->notify(new DueTasksReminder(1, 0));
        $notification = $other->notifications()->firstOrFail();

        $this->actingAs($user)->post(route('notifications.read', $notification->id))->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }
}
