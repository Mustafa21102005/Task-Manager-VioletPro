<?php

namespace App\Http\Controllers;

use App\Enums\Priority;
use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $today = today();

        // --- Counters: one query instead of four ---
        $counts = $user->tasks()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('COALESCE(SUM(is_completed = 1), 0) AS completed')
            ->selectRaw('COALESCE(SUM(is_completed = 0 AND due_date = ?), 0) AS due_today', [$today->toDateString()])
            ->selectRaw('COALESCE(SUM(is_completed = 0 AND due_date < ?), 0) AS overdue', [$today->toDateString()])
            ->first();

        $total = (int) $counts->total;
        $completed = (int) $counts->completed;

        $stats = [
            'total' => $total,
            'completed' => $completed,
            'open' => $total - $completed,
            'due_today' => (int) $counts->due_today,
            'overdue' => (int) $counts->overdue,
            'completion_rate' => $total > 0 ? (int) round($completed / $total * 100) : 0,
        ];

        // --- Focus list: overdue/due today first, then by priority, then by date ---
        $focusTasks = $user->tasks()
            ->with('category:id,name')
            ->where('is_completed', false)
            ->orderByRaw('(due_date IS NOT NULL AND due_date <= ?) DESC', [$today->toDateString()])
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->orderBy('id')
            ->limit(5)
            ->get()
            ->map(fn(Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'due_date' => $task->due_date?->format('Y-m-d'),
                'due_status' => match (true) {
                    $task->due_date === null => null,
                    $task->due_date->lt($today) => 'overdue',
                    $task->due_date->isSameDay($today) => 'today',
                    default => null,
                },
                'priority' => $task->priority->value,
                'priority_label' => $task->priority->label(),
                'category_name' => $task->category?->name,
            ]);

        // --- Open tasks by priority (every level appears, even with 0) ---
        $priorityCounts = $user->tasks()
            ->where('is_completed', false)
            ->selectRaw('priority, COUNT(*) AS count')
            ->groupBy('priority')
            ->pluck('count', 'priority');

        $byPriority = collect(Priority::cases())->map(fn(Priority $priority) => [
            'priority' => $priority->value,
            'label' => $priority->label(),
            'count' => (int) ($priorityCounts[$priority->value] ?? 0),
        ])->values();

        // --- Open tasks by category (only categories that have some) ---
        $byCategory = $user->categories()
            ->withCount(['tasks as open_tasks_count' => fn($query) => $query->where('is_completed', false)])
            ->orderByDesc('open_tasks_count')
            ->orderBy('name')
            ->get()
            ->filter(fn(Category $category) => $category->open_tasks_count > 0)
            ->map(fn(Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'count' => $category->open_tasks_count,
            ])
            ->values();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'focusTasks' => $focusTasks,
            'byPriority' => $byPriority,
            'byCategory' => $byCategory,
            'activity' => $this->activity($user),
            'progress' => $this->progress($user),
        ]);
    }

    private function activity(User $user): array
    {
        $today = today();

        // Completed tasks per day for the last 365 days, in one query
        // e.g. ['2026-10-03' => 4, '2026-10-02' => 1, ...]
        $counts = $user->tasks()
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', today()->subDays(364))
            ->selectRaw('DATE(completed_at) AS completed_day, COUNT(*) AS total')
            ->groupBy('completed_day')
            ->pluck('total', 'completed_day');

        // Current streak: walk backwards from today until a day with no completions
        $streak = 0;
        $cursor = $today->copy();

        if (! $counts->has($cursor->toDateString())) {
            $cursor->subDay(); // today isn't over yet, so start from yesterday
        }

        while ($counts->has($cursor->toDateString())) {
            $streak++;
            $cursor->subDay();
        }

        // Best streak: walk forward through the whole window
        $best = 0;
        $run = 0;

        for ($i = 364; $i >= 0; $i--) {
            $run = $counts->has($today->copy()->subDays($i)->toDateString()) ? $run + 1 : 0;
            $best = max($best, $run);
        }

        // This week, Monday to Sunday
        $weekStart = $today->copy()->startOfWeek(CarbonInterface::MONDAY);

        $week = collect(range(0, 6))->map(function (int $i) use ($weekStart, $counts, $today) {
            $day = $weekStart->copy()->addDays($i);

            return [
                'date' => $day->toDateString(),
                'count' => (int) $counts->get($day->toDateString(), 0),
                'is_today' => $day->isSameDay($today),
                'is_future' => $day->gt($today),
            ];
        });

        // Compare with the same days of last week, so Monday doesn't look like a failure
        $elapsed = $today->dayOfWeekIso; // Monday = 1 ... Sunday = 7
        $lastWeekStart = $weekStart->copy()->subWeek();

        $lastWeek = collect(range(0, $elapsed - 1))->sum(
            fn(int $i) => (int) $counts->get($lastWeekStart->copy()->addDays($i)->toDateString(), 0)
        );

        return [
            'week' => $week->values(),
            'this_week' => $week->sum('count'),
            'last_week' => $lastWeek,
            'current_streak' => $streak,
            'best_streak' => $best,
            'completed_today' => (int) $counts->get($today->toDateString(), 0),
        ];
    }

    private function progress(User $user): array
    {
        $today = today();

        $periods = [
            'today' => [$today, $today],
            'week' => [$today->copy()->startOfWeek(CarbonInterface::MONDAY), $today->copy()->endOfWeek(CarbonInterface::SUNDAY)],
            'month' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
        ];

        // One query: for each period, how many tasks are due in it and how many of those are completed
        $query = $user->tasks();

        foreach ($periods as $key => [$from, $to]) {
            $range = [$from->toDateString(), $to->toDateString()];

            $query
                ->selectRaw("COALESCE(SUM(due_date BETWEEN ? AND ?), 0) AS {$key}_total", $range)
                ->selectRaw("COALESCE(SUM((due_date BETWEEN ? AND ?) AND is_completed = 1), 0) AS {$key}_completed", $range);
        }

        $row = $query->first();

        return collect($periods)->map(function ($range, $key) use ($row) {
            $total = (int) $row->{"{$key}_total"};
            $completed = (int) $row->{"{$key}_completed"};

            return [
                'total' => $total,
                'completed' => $completed,
                'percent' => $total > 0 ? (int) round($completed / $total * 100) : 0,
            ];
        })->all();
    }
}
