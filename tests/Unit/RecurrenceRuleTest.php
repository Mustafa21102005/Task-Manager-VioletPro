<?php

namespace Tests\Unit;

use App\Enums\RecurrenceUnit;
use App\Support\RecurrenceRule;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RecurrenceRuleTest extends TestCase
{
    public static function nextDateCases(): array
    {
        $day = RecurrenceUnit::Day;
        $week = RecurrenceUnit::Week;
        $month = RecurrenceUnit::Month;

        // [unit, every N, weekdays (1 = Monday), due date, today, expected next date]
        return [
            'every Monday, due on a Wednesday' => [$week, 1, [1], '2026-10-07', '2026-10-07', '2026-10-12'],
            'Monday and Thursday, done on the Monday' => [$week, 1, [1, 4], '2026-10-05', '2026-10-05', '2026-10-08'],
            'Monday and Thursday, done on the Thursday' => [$week, 1, [1, 4], '2026-10-08', '2026-10-08', '2026-10-12'],
            'every 2 weeks' => [$week, 2, [], '2026-10-05', '2026-10-05', '2026-10-19'],
            'weekly, finished late' => [$week, 1, [1], '2026-10-05', '2026-10-21', '2026-10-26'],
            'weekly, finished early' => [$week, 1, [], '2026-10-09', '2026-10-06', '2026-10-16'],
            'every 3 days' => [$day, 3, [], '2026-10-05', '2026-10-05', '2026-10-08'],
            'daily, 3 days overdue' => [$day, 1, [], '2026-10-02', '2026-10-05', '2026-10-06'],
            'monthly clamps to the end of a short month' => [$month, 1, [], '2026-01-31', '2026-01-31', '2026-02-28'],
            'monthly goes back to the 31st' => [$month, 1, [], '2026-01-31', '2026-02-28', '2026-03-31'],
            'every 2 months' => [$month, 2, [], '2026-10-31', '2026-10-31', '2026-12-31'],
        ];
    }

    #[DataProvider('nextDateCases')]
    public function test_it_finds_the_next_date(RecurrenceUnit $unit, int $interval, array $days, string $due, string $today, string $expected): void
    {
        $rule = new RecurrenceRule($unit, $interval, $days);

        $next = $rule->nextDate(CarbonImmutable::parse($due), CarbonImmutable::parse($today));

        $this->assertSame($expected, $next->toDateString());
    }

    public static function labelCases(): array
    {
        return [
            'daily' => [RecurrenceUnit::Day, 1, [], 'Every day'],
            'every 3 days' => [RecurrenceUnit::Day, 3, [], 'Every 3 days'],
            'weekly' => [RecurrenceUnit::Week, 1, [], 'Every week'],
            'weekdays are sorted' => [RecurrenceUnit::Week, 1, [4, 1], 'Every Mon, Thu'],
            'every 2 weeks' => [RecurrenceUnit::Week, 2, [], 'Every 2 weeks'],
            'every 2 weeks on a day' => [RecurrenceUnit::Week, 2, [1], 'Every 2 weeks on Mon'],
            'monthly' => [RecurrenceUnit::Month, 1, [], 'Every month'],
            'every 2 months' => [RecurrenceUnit::Month, 2, [], 'Every 2 months'],
        ];
    }

    #[DataProvider('labelCases')]
    public function test_it_describes_itself(RecurrenceUnit $unit, int $interval, array $days, string $expected): void
    {
        $this->assertSame($expected, (new RecurrenceRule($unit, $interval, $days))->label());
    }
}
