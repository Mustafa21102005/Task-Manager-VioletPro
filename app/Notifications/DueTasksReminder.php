<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class DueTasksReminder extends Notification
{
    public function __construct(
        public int $dueToday,
        public int $overdue,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Tasks need your attention',
            'message' => $this->message(),
            'due_today' => $this->dueToday,
            'overdue' => $this->overdue,
            'url' => route('dashboard', absolute: false),
        ];
    }

    private function message(): string
    {
        $parts = [];

        if ($this->dueToday > 0) {
            $parts[] = $this->dueToday . ' ' . Str::plural('task', $this->dueToday) . ' due today';
        }

        if ($this->overdue > 0) {
            $parts[] = $this->overdue . ' overdue';
        }

        return 'You have ' . implode(' and ', $parts) . '.';
    }
}
