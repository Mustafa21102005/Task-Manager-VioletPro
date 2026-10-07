<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaskResource;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $today = CarbonImmutable::today();
        $month = $this->resolveMonth($request->query('month'));

        // The grid shows whole weeks, so it includes a few days from the neighbouring months
        $gridStart = $month->startOfMonth()->startOfWeek(CarbonInterface::MONDAY);
        $gridEnd = $month->endOfMonth()->endOfWeek(CarbonInterface::SUNDAY);

        $tasks = $request->user()->tasks()
            ->with('category:id,name')
            ->whereBetween('due_date', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->orderBy('is_completed')
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->orderBy('id')
            ->get();

        $days = collect(CarbonPeriod::create($gridStart, $gridEnd))->map(fn(CarbonInterface $day) => [
            'date' => $day->toDateString(),
            'day' => $day->day,
            'in_month' => $day->isSameMonth($month),
            'is_today' => $day->isSameDay($today),
            'is_past' => $day->lt($today),
        ])->values();

        return Inertia::render('calendar/Index', [
            'month' => $month->format('Y-m'),
            'prevMonth' => $month->subMonth()->format('Y-m'),
            'nextMonth' => $month->addMonth()->format('Y-m'),
            'today' => $today->toDateString(),
            'days' => $days,
            'tasks' => TaskResource::collection($tasks)->resolve(),
            'categories' => $request->user()->categories()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    // "2026-10" -> first day of that month; anything else falls back to the current month
    private function resolveMonth(mixed $value): CarbonImmutable
    {
        if (is_string($value) && preg_match('/^(19|20)\d{2}-(0[1-9]|1[0-2])$/', $value)) {
            return CarbonImmutable::createFromFormat('!Y-m', $value);
        }

        return CarbonImmutable::today()->startOfMonth();
    }
}
