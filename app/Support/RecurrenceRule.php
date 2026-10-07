<?php

namespace App\Support;

use App\Enums\RecurrenceUnit;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final class RecurrenceRule
{
    private const WEEKDAYS = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

    /**
     * @param  list<int>  $days  ISO weekdays (1 = Sunday ... 7 = Saturday), only used for weekly rules
     */
    public function __construct(
        public readonly RecurrenceUnit $unit,
        public readonly int $interval = 1,
        public readonly array $days = [],
    ) {}

    /**
     * First date that fits the rule and comes after both the due date and today.
     */
    public function nextDate(CarbonInterface $due, CarbonInterface $today): CarbonImmutable
    {
        $due = CarbonImmutable::instance($due)->startOfDay();
        $reference = $due->max(CarbonImmutable::instance($today)->startOfDay());

        return $this->unit === RecurrenceUnit::Week
            ? $this->nextWeeklyDate($due, $reference)
            : $this->nextSteppedDate($due, $reference);
    }

    public function label(): string
    {
        if ($this->unit === RecurrenceUnit::Week) {
            return $this->weekLabel();
        }

        if ($this->interval === 1) {
            return 'Every ' . $this->unit->value;
        }

        return "Every {$this->interval} " . $this->unit->value . 's';
    }

    // Days and months: due date + 1 step, + 2 steps, ... until it is after the reference
    private function nextSteppedDate(CarbonImmutable $due, CarbonImmutable $reference): CarbonImmutable
    {
        $step = 1;

        do {
            $next = $this->unit === RecurrenceUnit::Day
                ? $due->addDays($step * $this->interval)
                : $due->addMonthsNoOverflow($step * $this->interval);

            $step++;
        } while ($next->lte($reference));

        return $next;
    }

    // Weeks: check each day after the reference until it is on a chosen weekday
    // in a week that is a multiple of the interval away from the first week
    private function nextWeeklyDate(CarbonImmutable $due, CarbonImmutable $reference): CarbonImmutable
    {
        // No weekdays chosen: repeat on the weekday of the due date
        $days = $this->days ?: [$due->dayOfWeekIso];
        $firstWeek = $due->startOfWeek(CarbonInterface::MONDAY);

        $candidate = $reference->addDay();

        for ($i = 0; $i < 7 * $this->interval + 7; $i++) {
            // round(), because a daylight-saving change can make the difference 6.96 instead of 7
            $weeksSinceStart = intdiv((int) round($firstWeek->diffInDays($candidate)), 7);

            if ($weeksSinceStart % $this->interval === 0 && in_array($candidate->dayOfWeekIso, $days, true)) {
                return $candidate;
            }

            $candidate = $candidate->addDay();
        }

        return $reference->addWeeks($this->interval); // safety net, should never be reached
    }

    private function weekLabel(): string
    {
        $names = collect($this->days)
            ->sort()
            ->map(fn(int $day) => self::WEEKDAYS[$day])
            ->implode(', ');

        if ($this->interval === 1) {
            return $names === '' ? 'Every week' : "Every {$names}";
        }

        return $names === '' ? "Every {$this->interval} weeks" : "Every {$this->interval} weeks on {$names}";
    }
}
