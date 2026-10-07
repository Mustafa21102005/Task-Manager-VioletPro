<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\DueTasksReminder;
use Illuminate\Console\Command;

class SendDueReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify users who have tasks due today or overdue';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = today()->toDateString();
        $sent = 0;

        User::query()
            ->whereHas('tasks', fn($query) => $query
                ->where('is_completed', false)
                ->whereDate('due_date', '<=', $today))
            ->each(function (User $user) use ($today, &$sent) {
                // One reminder per user per day, even if the command runs twice
                $alreadySent = $user->notifications()
                    ->where('type', DueTasksReminder::class)
                    ->where('created_at', '>=', today())
                    ->exists();

                if ($alreadySent) {
                    return;
                }

                $counts = $user->tasks()
                    ->where('is_completed', false)
                    ->selectRaw('COALESCE(SUM(due_date = ?), 0) AS due_today', [$today])
                    ->selectRaw('COALESCE(SUM(due_date < ?), 0) AS overdue', [$today])
                    ->first();

                $user->notify(new DueTasksReminder((int) $counts->due_today, (int) $counts->overdue));
                $sent++;
            });

        $this->info("Sent {$sent} reminder(s).");

        return self::SUCCESS;
    }
}
